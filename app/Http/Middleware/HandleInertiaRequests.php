<?php

namespace App\Http\Middleware;

use App\Models\Category;
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
        return parent::version($request);
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
        $partner = ($space && $user) ? $space->getPartnerOf($user) : null;

        $wallets = $space ? Wallet::where('couple_space_id', $space->id)->where('is_active', true)->get() : [];
        $categories = $space ? Category::where(function ($q) use ($space) {
            $q->whereNull('couple_space_id')->orWhere('couple_space_id', $space->id);
        })->get() : Category::whereNull('couple_space_id')->get();

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
        ];
    }
}
