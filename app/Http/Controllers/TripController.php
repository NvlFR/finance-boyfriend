<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\PushSubscription;
use App\Models\Trip;
use App\Models\Wallet;
use App\Services\PushNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TripController extends Controller
{
    public function __construct(private PushNotificationService $pushNotificationService) {}

    /**
     * Display live trip tracker and trip history.
     */
    public function index(Request $request): JsonResponse|Response
    {
        $user = $request->user();
        $space = $user->getOrEnsureCoupleSpace();
        $space->load(['userOne', 'userTwo']);

        $activeTrip = Trip::where('couple_space_id', $space->id)
            ->where('status', 'active')
            ->with('user')
            ->latest('started_at')
            ->first();

        $trips = Trip::where('couple_space_id', $space->id)
            ->with('user')
            ->latest('started_at')
            ->paginate(15);

        $partner = $space->user_one_id === $user->id ? $space->userTwo : $space->userOne;

        $wallets = Wallet::where('couple_space_id', $space->id)->where('is_active', true)->get();
        $categories = Category::whereNull('couple_space_id')->orWhere('couple_space_id', $space->id)->get();

        $data = [
            'activeTrip' => $activeTrip,
            'trips' => $trips,
            'wallets' => $wallets,
            'categories' => $categories,
            'partner' => $partner,
            'auth' => [
                'user' => $user,
            ],
            'pushPublicKey' => config('services.web_push.public_key'),
        ];

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return Inertia::render('Trips/Index', $data);
    }

    /**
     * Start a new live trip and notify partner.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $space = $user->getOrEnsureCoupleSpace();

        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'destination_name' => 'nullable|string|max:150',
            'origin_name' => 'nullable|string|max:150',
            'origin_lat' => 'nullable|numeric|between:-90,90',
            'origin_lng' => 'nullable|numeric|between:-180,180',
            'destination_lat' => 'nullable|numeric|between:-90,90',
            'destination_lng' => 'nullable|numeric|between:-180,180',
            'notes' => 'nullable|string',
        ]);

        $trip = DB::transaction(function () use ($space, $user, $validated): Trip {
            $lockedSpace = $space->newQuery()->whereKey($space->id)->lockForUpdate()->firstOrFail();
            $partnerTripExists = Trip::where('couple_space_id', $lockedSpace->id)
                ->where('user_id', '!=', $user->id)
                ->where('status', 'active')
                ->exists();

            if ($partnerTripExists) {
                throw ValidationException::withMessages([
                    'title' => 'Pasanganmu sedang membagikan perjalanan aktif. Tunggu sampai perjalanan selesai.',
                ]);
            }

            Trip::where('couple_space_id', $lockedSpace->id)
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->update(['status' => 'completed', 'ended_at' => now()]);

            return Trip::create([
                'couple_space_id' => $lockedSpace->id,
                'user_id' => $user->id,
                'title' => $validated['title'],
                'origin_name' => $validated['origin_name'] ?? 'Lokasi Saat Ini',
                'destination_name' => $validated['destination_name'] ?? null,
                'origin_lat' => $validated['origin_lat'] ?? null,
                'origin_lng' => $validated['origin_lng'] ?? null,
                'current_lat' => $validated['origin_lat'] ?? null,
                'current_lng' => $validated['origin_lng'] ?? null,
                'destination_lat' => $validated['destination_lat'] ?? null,
                'destination_lng' => $validated['destination_lng'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => 'active',
                'started_at' => now(),
            ]);
        }, attempts: 3);

        $partner = $space->getPartnerOf($user);
        if ($partner) {
            $this->pushNotificationService->sendTo($partner, [
                'title' => 'Pasanganmu mulai perjalanan',
                'body' => "{$user->nicknameOrName()} sedang {$trip->title}.",
                'url' => route('trips.index'),
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Perjalanan berhasil dimulai!',
                'trip' => $trip,
            ], 201);
        }

        return redirect()->back()->with('success', 'Perjalanan berhasil dimulai! 🚗');
    }

    /**
     * Update current GPS location and speed during active trip.
     */
    public function updatePosition(Request $request, Trip $trip): JsonResponse
    {
        $user = $request->user();

        if ($trip->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'speed' => 'nullable|numeric|min:0',
        ]);

        $trip = DB::transaction(function () use ($trip, $user, $validated): Trip {
            $lockedTrip = Trip::query()->whereKey($trip->id)->lockForUpdate()->firstOrFail();

            if ($lockedTrip->user_id !== $user->id || $lockedTrip->status !== 'active') {
                abort(403);
            }

            $speed = max(0, (float) ($validated['speed'] ?? 0));
            $distance = ($lockedTrip->current_lat !== null && $lockedTrip->current_lng !== null)
                ? $this->distanceInKilometers(
                    (float) $lockedTrip->current_lat,
                    (float) $lockedTrip->current_lng,
                    (float) $validated['lat'],
                    (float) $validated['lng'],
                )
                : 0.0;

            $lockedTrip->update([
                'current_lat' => $validated['lat'],
                'current_lng' => $validated['lng'],
                'speed' => $speed,
                'max_speed' => max((float) $lockedTrip->max_speed, $speed),
                'total_distance_km' => (float) $lockedTrip->total_distance_km + $distance,
            ]);

            return $lockedTrip->fresh();
        });

        return response()->json([
            'status' => 'ok',
            'trip' => $trip,
        ]);
    }

    /**
     * Complete an active trip.
     */
    public function complete(Request $request, Trip $trip): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        if ($trip->user_id !== $user->id || $trip->status !== 'active') {
            abort(403);
        }

        $trip->update([
            'status' => 'completed',
            'ended_at' => now(),
        ]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Trip completed']);
        }

        return redirect()->back()->with('success', 'Perjalanan selesai! 🎉');
    }

    /**
     * Save browser push notification subscription endpoint.
     */
    public function subscribePush(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'endpoint' => 'required|url|max:2048',
            'public_key' => 'required|string|max:255',
            'auth_token' => 'required|string|max:255',
            'content_encoding' => 'nullable|in:aesgcm,aes128gcm',
        ]);

        PushSubscription::updateOrCreate(
            [
                'endpoint_hash' => hash('sha256', $validated['endpoint']),
            ],
            [
                'user_id' => $user->id,
                'endpoint' => $validated['endpoint'],
                'public_key' => $validated['public_key'] ?? null,
                'auth_token' => $validated['auth_token'] ?? null,
                'content_encoding' => $validated['content_encoding'] ?? 'aesgcm',
            ]
        );

        return response()->json(['status' => 'subscribed']);
    }

    private function distanceInKilometers(float $fromLat, float $fromLng, float $toLat, float $toLng): float
    {
        $earthRadius = 6371.0;
        $latitudeDelta = deg2rad($toLat - $fromLat);
        $longitudeDelta = deg2rad($toLng - $fromLng);
        $haversine = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($fromLat)) * cos(deg2rad($toLat)) * sin($longitudeDelta / 2) ** 2;

        return round($earthRadius * 2 * atan2(sqrt($haversine), sqrt(1 - $haversine)), 4);
    }
}
