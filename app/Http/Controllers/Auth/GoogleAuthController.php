<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Redirect to Google OAuth Provider.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Callback from Google.
     */
    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Gagal terhubung dengan akun Google. Silakan coba lagi.');
        }

        // 1. Check if user already exists with this google_id
        $user = User::where('google_id', $googleUser->getId())->first();

        if (! $user) {
            // 2. Check if email already registered via normal register
            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                // Link existing email user with google_id
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'avatar_url' => $user->avatar_url ?? $googleUser->getAvatar(),
                ]);
            } else {
                // 3. Register brand new user from Google
                $user = User::create([
                    'name' => $googleUser->getName() ?? $googleUser->getNickname() ?? 'Pengguna Google',
                    'nickname' => explode(' ', $googleUser->getName() ?? 'Google')[0],
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'avatar_url' => $googleUser->getAvatar(),
                    'email_verified_at' => now(),
                    'password' => null,
                ]);
            }
        }

        Auth::login($user, true);

        return redirect()->intended('/dashboard');
    }
}
