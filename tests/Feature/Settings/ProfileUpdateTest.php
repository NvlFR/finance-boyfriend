<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('profile.edit'));

    $response->assertOk();
});

test('profile only shows pairing code before a partner joins and has no account deletion section', function () {
    $profilePage = file_get_contents(resource_path('js/pages/settings/Profile.vue'));

    expect($profilePage)
        ->toContain('v-if="coupleSpace?.invite_code && !partner"')
        ->not->toContain("import DeleteUser from '@/components/DeleteUser.vue'")
        ->not->toContain('<DeleteUser />');
});

test('personal information form is hidden behind an accessible settings menu item', function () {
    $profilePage = file_get_contents(resource_path('js/pages/settings/Profile.vue'));

    expect($profilePage)
        ->toContain('const isProfileDetailsOpen = ref(false)')
        ->toContain(':aria-expanded="isProfileDetailsOpen"')
        ->toContain('aria-controls="profile-details-panel"')
        ->toContain('v-show="isProfileDetailsOpen"')
        ->toContain('Informasi Data Diri')
        ->toContain('Edit profil kamu');
});

test('profile couple hero reuses the custom couple space cover', function () {
    $profilePage = file_get_contents(resource_path('js/pages/settings/Profile.vue'));

    expect($profilePage)
        ->toContain('v-if="coupleSpace?.dashboard_cover_url"')
        ->toContain(':src="coupleSpace.dashboard_cover_url"')
        ->toContain('from-slate-950/85 via-zinc-950/70 to-rose-950/75');
});

test('settings layout uses the same page header as finance pages', function () {
    $settingsLayout = file_get_contents(resource_path('js/layouts/settings/Layout.vue'));

    expect($settingsLayout)
        ->toContain("import PageHeader from '@/components/PageHeader.vue'")
        ->toContain('title="Pengaturan Akun"')
        ->toContain('aria-label="Navigasi pengaturan akun"')
        ->toContain('grid grid-cols-3 gap-1')
        ->toContain('bg-indigo-900 text-white');
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('profile photo is persisted and remains available after navigation', function () {
    Storage::fake('public');

    $user = User::factory()->create([
        'avatar_url' => '/storage/avatars/old-avatar.jpg',
    ]);
    Storage::disk('public')->put('avatars/old-avatar.jpg', 'old avatar');

    $this->actingAs($user)
        ->post(route('profile.avatar.update'), [
            'avatar' => UploadedFile::fake()->image('new-avatar.jpg', 300, 300),
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $avatarPath = Str::after($user->fresh()->avatar_url, '/storage/');

    Storage::disk('public')->assertExists($avatarPath);
    Storage::disk('public')->assertMissing('avatars/old-avatar.jpg');

    $this->actingAs($user->fresh())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.user.avatar_url', '/storage/'.$avatarPath));
});

test('profile photo upload rejects non image files', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('profile.avatar.update'), [
            'avatar' => UploadedFile::fake()->create('avatar.pdf', 100, 'application/pdf'),
        ])
        ->assertSessionHasErrors('avatar');

    expect($user->fresh()->avatar_url)->toBeNull();
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('profile.destroy'), [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home'));

    $this->assertGuest();
    expect($user->fresh())->toBeNull();
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh())->not->toBeNull();
});
