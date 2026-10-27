<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function create()
    {
        return view('pages.register');
    }

    public function store(Request $request): RedirectResponse
    {
        // Telefonni tekshiruvdan oldin yagona formatga keltiramiz
        $request->merge(['phone' => Phone::normalize($request->input('phone'))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'phone' => ['required', 'unique:users,phone'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'name.required' => 'Ism va familiyani kiriting.',
            'name.min' => 'Ism kamida 3 ta belgidan iborat bo\'lsin.',
            'phone.required' => 'To\'g\'ri telefon raqam kiriting (masalan: +998 90 123 45 67).',
            'phone.unique' => 'Bu telefon raqam allaqachon ro\'yxatdan o\'tgan.',
            'password.required' => 'Parolni kiriting.',
            'password.confirmed' => 'Parollar bir xil emas.',
            'password.min' => 'Parol kamida 8 ta belgidan iborat bo\'lsin.',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'password' => $data['password'], // modelda 'hashed' cast
            'role' => 'student',
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        $user->forceFill(['current_session_id' => $request->session()->getId()])->save();

        return redirect()->route('courses.index')
            ->with('success', "Xush kelibsiz, {$user->name}! Birinchi darsni bepul ko'rishingiz mumkin.");
    }
}
