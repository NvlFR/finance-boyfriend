<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Testing\AssertableInertia as Assert;

test('authenticated pages share the current application release', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('appRelease.version', '1.1.3')
            ->where('appRelease.title', 'Rincian total kekayaan lebih jelas')
            ->where('appRelease.released_at', '2026-09-14')
            ->has('appRelease.highlights', 2));
});

test('release version participates in inertia asset versioning', function () {
    $middleware = app(HandleInertiaRequests::class);
    $request = Request::create('/dashboard');
    $originalVersion = config('releases.current_version');

    $firstAssetVersion = $middleware->version($request);
    config()->set('releases.current_version', '99.0.0');
    $nextAssetVersion = $middleware->version($request);
    config()->set('releases.current_version', $originalVersion);

    expect($firstAssetVersion)
        ->not->toBeNull()
        ->not->toBe($nextAssetVersion);
});

test('release announcement is mounted globally and remembered per user', function () {
    $layout = file_get_contents(resource_path('js/layouts/AppLayout.vue'));
    $settingsLayout = file_get_contents(resource_path('js/layouts/settings/Layout.vue'));
    $announcement = file_get_contents(resource_path('js/components/ReleaseUpdateModal.vue'));

    expect(config('releases.current_version'))
        ->toMatch('/^\d+\.\d+\.\d+$/')
        ->and(config('releases.items'))
        ->toHaveCount(4)
        ->and($layout)
        ->toContain("import ReleaseUpdateModal from '@/components/ReleaseUpdateModal.vue'")
        ->toContain(':release="appRelease"')
        ->toContain(':user-id="user.id"')
        ->and($settingsLayout)
        ->toContain('Couple Finance v{{ appRelease.version }}')
        ->and($announcement)
        ->toContain('finance-couple:last-seen-release:${props.userId}')
        ->toContain('window.localStorage.getItem(storageKey.value)')
        ->toContain('window.localStorage.setItem(')
        ->toContain('props.release.version');
});
