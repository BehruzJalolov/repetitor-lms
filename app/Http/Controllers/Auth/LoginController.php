<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            'phone.required' => 'Telefon raqamni kiriting.',
            'password.required' => 'Parolni kiriting.',
        ]);

        $phone = Phone::normalize($request->input('phone'));
        $throttleKey = Str::lower(($phone ?? $request->input('phone')).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'phone' => "Juda ko'p urinish. {$seconds} soniyadan keyin qayta urinib ko'ring.",
            ]);
        }

        if (! $phone || ! Auth::attempt(['phone' => $phone, 'password' => $request->input('password')], $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'phone' => "Telefon raqam yoki parol noto'g'ri.",
            ]);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::user();
        // Yangi sessiya faol bo'ladi, eski qurilmadagi sessiya o'chadi
        $user->forceFill(['current_session_id' => $request->session()->getId()])->save();

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
