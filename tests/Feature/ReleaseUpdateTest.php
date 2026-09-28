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
            ->where('appRelease.version', '1.1.8')
            ->where('appRelease.title', 'Perlindungan data dan keamanan akun')
            ->where('appRelease.released_at', '2026-09-27')
            ->has('appRelease.highlights', 4)
            ->has('appRelease.tour', 3)
            ->where('appRelease.tour.0.path', '/dashboard')
            ->where('appRelease.tour.1.path', '/transactions')
            ->where('appRelease.tour.2.path', '/goals'));
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
    $tour = file_get_contents(resource_path('js/components/FeatureTour.vue'));

    expect(config('releases.current_version'))
        ->toMatch('/^\d+\.\d+\.\d+$/')
        ->and(config('releases.items'))
        ->toHaveCount(9)
        ->and($layout)
        ->toContain("import ReleaseUpdateModal from '@/components/ReleaseUpdateModal.vue'")
        ->toContain(':release="appRelease"')
        ->toContain(':user-id="user.id"')
        ->toContain('@start-tour="featureTourRequest += 1"')
        ->and($settingsLayout)
        ->toContain('Couple Finance v{{ appRelease.version }}')
        ->and($announcement)
        ->toContain('finance-couple:last-seen-release:${props.userId}')
        ->toContain('window.localStorage.getItem(storageKey.value)')
        ->toContain('window.localStorage.setItem(')
        ->toContain('props.release.version');
    expect($tour)
        ->toContain('finance-couple:feature-tour:${props.userId}')
        ->toContain('router.visit(step.path')
        ->toContain('replace: true')
        ->toContain('document.querySelector<HTMLElement>(step.target)')
        ->toContain(':class="{ invisible: isWaitingForTarget }"')
        ->toContain('useAccessibleDialog(')
        ->toContain('aria-labelledby="feature-tour-title"')
        ->toContain('@keydown="handleDialogKeydown"')
        ->toContain("window.addEventListener('finance:start-feature-tour'")
        ->and($settingsLayout)
        ->toContain('Lihat tur fitur');
});
