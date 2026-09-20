<?php

namespace App\Http\Middleware;

use App\Models\Category;
use App\Models\SavingsGoal;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        $assetVersion = parent::version($request);
        $releaseVersion = config('releases.current_version');

        if (! $assetVersion && ! $releaseVersion) {
            return null;
        }

        return hash('xxh128', $assetVersion.'|'.$releaseVersion);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $space = $user ? $user->currentCoupleSpace : null;
        $partner = $space ? $space->getPartnerOf($user) : null;

        $wallets = $space ? Wallet::where('couple_space_id', $space->id)
            ->where('is_active', true)
            ->with('user:id,name,nickname')
            ->get(['id', 'couple_space_id', 'user_id', 'name', 'type', 'wallet_type', 'balance', 'currency', 'color', 'icon', 'is_active']) : [];
        $categories = $space ? Category::where(function ($q) use ($space) {
            $q->whereNull('couple_space_id')->orWhere('couple_space_id', $space->id);
        })->get() : Category::whereNull('couple_space_id')->get();
        $emergencySavingsGoals = $space ? SavingsGoal::query()
            ->where('couple_space_id', $space->id)
            ->where('is_emergency_fund', true)
            ->where(fn ($query) => $query
                ->where('scope', 'shared')
                ->orWhere('created_by_user_id', $user->id))
            ->get(['id', 'created_by_user_id', 'scope', 'name', 'current_amount', 'color']) : [];

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
            ],
            'coupleSpace' => $space ? $space->load(['userOne', 'userTwo']) : null,
            'partner' => $partner,
            'wallets' => $wallets,
            'categories' => $categories,
            'emergencySavingsGoals' => $emergencySavingsGoals,
            'appRelease' => $user ? $this->currentRelease() : null,
            'statusMessage' => fn (): ?array => session('success')
                ? ['type' => 'success', 'message' => session('success')]
                : (session('error') ? ['type' => 'error', 'message' => session('error')] : null),
        ];
    }

    /**
     * @return array{version: string, title: string, released_at: string, highlights: list<string>}|null
     */
    private function currentRelease(): ?array
    {
        $currentVersion = config('releases.current_version');
        $releases = config('releases.items', []);

        if (! is_string($currentVersion) || ! is_array($releases)) {
            return null;
        }

        foreach ($releases as $release) {
            if (! is_array($release) || ($release['version'] ?? null) !== $currentVersion) {
                continue;
            }

            $title = $release['title'] ?? null;
            $releasedAt = $release['released_at'] ?? null;
            $highlights = $release['highlights'] ?? null;

            if (! is_string($title) || ! is_string($releasedAt) || ! is_array($highlights)) {
                return null;
            }

            $validHighlights = array_values(array_filter($highlights, is_string(...)));

            if (count($validHighlights) !== count($highlights)) {
                return null;
            }

            return [
                'version' => $currentVersion,
                'title' => $title,
                'released_at' => $releasedAt,
                'highlights' => $validHighlights,
            ];
        }

        return null;
    }
}
