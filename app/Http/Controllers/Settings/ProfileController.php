<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\AvatarUpdateRequest;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\CoupleSpace;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = $request->user();

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');

            if ($path === false) {
                throw new RuntimeException('Foto profil gagal disimpan.');
            }

            $user->avatar_url = Storage::url($path);
        }

        $user->name = $validated['name'];
        if (array_key_exists('nickname', $validated)) {
            $user->nickname = $validated['nickname'];
        }
        if (array_key_exists('theme_color', $validated)) {
            $user->theme_color = $validated['theme_color'];
        }
        if ($user->email !== $validated['email']) {
            $user->email = $validated['email'];
            $user->email_verified_at = null;
        }

        $user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile updated.')]);

        return to_route('profile.edit');
    }

    /**
     * Persist a newly selected profile photo immediately.
     */
    public function updateAvatar(AvatarUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $previousAvatarUrl = $user->avatar_url;
        $path = $request->file('avatar')->store('avatars', 'public');

        if ($path === false) {
            throw new RuntimeException('Foto profil gagal disimpan.');
        }

        $user->update(['avatar_url' => Storage::url($path)]);

        if ($previousAvatarUrl && str_starts_with($previousAvatarUrl, '/storage/')) {
            Storage::disk('public')->delete(Str::after($previousAvatarUrl, '/storage/'));
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Foto profil berhasil disimpan.']);

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        DB::transaction(function () use ($user): void {
            $lockedUser = $user->newQuery()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ($lockedUser->current_couple_space_id || CoupleSpace::query()
                ->where('user_one_id', $user->id)->orWhere('user_two_id', $user->id)->exists()) {
                throw ValidationException::withMessages([
                    'password' => 'Akun masih terhubung ruang keuangan. Penghapusan diblokir untuk melindungi saldo, riwayat, dan data pasangan.',
                ]);
            }
            $lockedUser->delete();
        });

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
