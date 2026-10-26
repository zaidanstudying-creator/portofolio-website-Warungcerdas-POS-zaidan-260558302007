<?php

namespace App\Http\Controllers;

use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\ResetPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi tidak sesuai.',
            ]);
        }

        if (! $request->user()->isActive()) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi tidak sesuai.',
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));

        $request->session()->regenerate();

        return redirect()->route('welcome');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah keluar.');
    }

    /**
     * Show the forgot password form.
     */
    public function forgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Send the password reset link email.
     */
    public function sendResetLinkEmail(ForgotPasswordRequest $request): RedirectResponse
    {
        $throttleKey = $this->forgotPasswordThrottleKey($request);

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'email' => 'Terlalu banyak percobaan. Silakan coba lagi dalam '.$seconds.' detik.',
            ]);
        }

        RateLimiter::hit($throttleKey, 900); // 15 minutes

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', 'Jika email terdaftar, tautan reset password telah dikirim.');
        }

        // Always return the same message to prevent email enumeration
        return back()->with('status', 'Jika email terdaftar, tautan reset password telah dikirim.');
    }

    /**
     * Show the reset password form.
     */
    public function resetPassword(Request $request, string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    /**
     * Reset the user's password.
     */
    public function updatePassword(ResetPasswordRequest $request): RedirectResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('success', 'Kata sandi berhasil direset. Silakan login.');
        }

        throw ValidationException::withMessages([
            'email' => 'Token reset password tidak valid atau telah kedaluwarsa.',
        ]);
    }

    private function throttleKey(Request $request): string
    {
        $key = Str::lower((string) $request->input('email')).'|'.$request->ip();

        return md5('login'.$key);
    }

    private function forgotPasswordThrottleKey(Request $request): string
    {
        $key = Str::lower((string) $request->input('email')).'|'.$request->ip();

        return md5('forgot-password'.$key);
    }
}
