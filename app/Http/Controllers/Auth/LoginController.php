<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function create()
    {
        return view('pages.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'phone.required' => 'Telefon raqam yoki emailni kiriting.',
            'password.required' => 'Parolni kiriting.',
        ]);

        $rawInput = trim((string) $request->input('phone'));
        $isEmail = str_contains($rawInput, '@');
        $identifier = $isEmail
            ? (filter_var(Str::lower($rawInput), FILTER_VALIDATE_EMAIL) ?: null)
            : Phone::normalize($rawInput);

        $throttleKey = Str::lower(($identifier ?? $rawInput).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'phone' => "Juda ko'p urinish. {$seconds} soniyadan keyin qayta urinib ko'ring.",
            ]);
        }

        $credentials = $isEmail
            ? ['email' => $identifier, 'password' => $request->input('password')]
            : ['phone' => $identifier, 'password' => $request->input('password')];

        if (! $identifier || ! Auth::attempt($credentials, false)) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'phone' => "Login ma'lumotlari yoki parol noto'g'ri.",
            ]);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::user();

        // Yangi remember_token beramiz (eski qurilmadagi "Meni eslab qol" cookie'si bekor bo'ladi)
        $user->setRememberToken(Str::random(60));

        if ($request->boolean('remember')) {
            Auth::login($user, true);
        }

        $currentSessionId = $request->session()->getId();

        if (! $user->isAdmin()) {
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('id', '!=', $currentSessionId)
                ->delete();
        }

        $user->forceFill([
            'current_session_id' => $currentSessionId,
            'remember_token' => $user->getRememberToken(),
        ])->save();

        return redirect()->intended($user->isAdmin() ? route('admin.dashboard') : route('home'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        if ($user = $request->user()) {
            $user->forceFill(['current_session_id' => null])->save();
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
