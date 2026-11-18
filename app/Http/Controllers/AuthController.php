<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login', [
            'googleConfigured' => $this->isGoogleOAuthConfigured(),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'))
                ->with('success', 'Welcome back to AIVI PRO, ' . Auth::user()->name . '!');
        }

        return back()
            ->withErrors(['email' => 'Invalid email or password. Please try again.'])
            ->onlyInput('email');
    }

    public function showRegister(): View
    {
        return view('auth.register', [
            'googleConfigured' => $this->isGoogleOAuthConfigured(),
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:180', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('success', 'Account created! You are now signed in to Mission Control.');
    }

    public function redirectToGoogle(): RedirectResponse
    {
        if (! $this->isGoogleOAuthConfigured()) {
            return redirect()->route('login')
                ->with('error', 'Google Login is not configured yet. Add GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in .env.');
        }

        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        if (! $this->isGoogleOAuthConfigured()) {
            return redirect()->route('login')
                ->with('error', 'Google Login is not configured yet. Add GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in .env.');
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            Log::warning('AIVI Google OAuth callback failed: ' . $e->getMessage());

            return redirect()->route('login')
                ->with('error', 'Google sign-in was cancelled or could not be verified. Please try again.');
        }

        $email = trim((string) $googleUser->getEmail());
        $googleId = (string) $googleUser->getId();

        if ($email === '' || $googleId === '') {
            return redirect()->route('login')
                ->with('error', 'Google did not provide a valid email address for your account.');
        }

        $user = User::where('google_id', $googleId)
            ->orWhere('email', $email)
            ->first();

        if ($user !== null) {
            $user->forceFill([
                'google_id' => $googleId,
                'avatar' => $googleUser->getAvatar() ?: $user->avatar,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
        } else {
            $user = User::create([
                'name' => $googleUser->getName() ?: Str::before($email, '@'),
                'email' => $email,
                'google_id' => $googleId,
                'avatar' => $googleUser->getAvatar(),
                'email_verified_at' => now(),
                'password' => Str::password(32),
            ]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'))
            ->with('success', 'Signed in with Google as ' . $user->name . '!');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'You have been logged out safely.');
    }

    private function isGoogleOAuthConfigured(): bool
    {
        $clientId = trim((string) config('services.google.client_id'));
        $clientSecret = trim((string) config('services.google.client_secret'));

        return $clientId !== ''
            && $clientSecret !== ''
            && ! str_starts_with($clientId, 'your_')
            && ! str_starts_with($clientSecret, 'your_');
    }
}
