<?php

namespace App\Http\Controllers;

use App\Mail\VerificationCodeMail;
use App\Services\EskizSmsService;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    private const EXPIRES_MINUTES = 15;

    private const RESEND_COOLDOWN_SECONDS = 60;

    public function edit(Request $request)
    {
        return view('pages.profile', [
            'user' => $request->user(),
            'pendingContact' => session('pending_profile_contact'),
            'passwordOtpTarget' => session('profile_password_otp_target'),
        ]);
    }

    /**
     * Ism, telefon yoki emailni yangilash.
     * Agar email o'zgargan bo'lsa — yangi emailga 6 xonali tasdiqlash kodi yuboriladi.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->merge([
            'phone' => Phone::normalize($request->input('phone')),
            'email' => $request->filled('email') ? mb_strtolower(trim((string) $request->input('email'))) : null,
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'phone' => ['required', Rule::unique('users', 'phone')->ignore($user->id)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ], [
            'name.required' => 'Ism va familiyani kiriting.',
            'name.min' => 'Ism kamida 3 ta belgidan iborat bo\'lsin.',
            'phone.required' => 'To\'g\'ri telefon raqam kiriting (+998 90 123 45 67).',
            'phone.unique' => 'Bu telefon raqam boshqa hisobga ulangan.',
            'email.required' => 'Email manzilni kiriting.',
            'email.email' => 'To\'g\'ri email manzil kiriting (masalan: ali@gmail.com).',
            'email.unique' => 'Bu email manzil boshqa hisobga ulangan.',
        ]);

        $user->name = $data['name'];
        $user->phone = $data['phone'];
        $user->save();

        // Agar email o'zgarmagan bo'lsa — darhol yakunlaymiz
        if ($user->email === $data['email']) {
            return back()->with('success', 'Profil ma\'lumotlari saqlandi.');
        }

        // Yangi email kiritilgan bo'lsa — avval 6 xonali kod bilan tasdiqlaymiz
        $tokenKey = 'email_verify:'.$user->id;
        $existing = DB::table('password_reset_tokens')->where('phone', $tokenKey)->first();

        if ($existing && $existing->created_at) {
            $availableAt = Carbon::parse($existing->created_at)->addSeconds(self::RESEND_COOLDOWN_SECONDS);
            if ($availableAt->isFuture()) {
                $secondsLeft = (int) ceil(now()->diffInSeconds($availableAt));

                return back()->withErrors([
                    'email' => "Yangi kod yuborish uchun {$secondsLeft} soniya kuting.",
                ]);
            }
        }

        $code = (string) random_int(100000, 999999);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['phone' => $tokenKey],
            [
                'token' => Hash::make($code),
                'attempts' => 0,
                'created_at' => now(),
            ]
        );

        try {
            Mail::to($data['email'])->send(new VerificationCodeMail(
                code: $code,
                userName: $user->name,
                purpose: 'Email manzilni tasdiqlash',
                description: 'Profilingizga yangi email manzilni ulash uchun quyidagi 6 xonali kodni saytga kiriting:',
            ));
        } catch (\Throwable $e) {
            Log::error('Profil email tasdiqlash xatosi: '.$e->getMessage());
            DB::table('password_reset_tokens')->where('phone', $tokenKey)->delete();

            return back()->with('error', 'Email manzilga kod yuborib bo\'lmadi.');
        }

        session([
            'pending_profile_contact' => [
                'email' => $data['email'],
            ],
        ]);

        return back()->with('success', "{$data['email']} manziliga 6 xonali tasdiqlash kodi yuborildi. Kodni kiriting.");
    }

    /**
     * Yangi email manzilga yuborilgan 6 xonali kodni tasdiqlash
     */
    public function verifyEmail(Request $request): RedirectResponse
    {
        $user = $request->user();
        $pending = session('pending_profile_contact');

        if (! $pending || empty($pending['email'])) {
            return redirect()->route('profile.edit')->with('error', 'Tasdiqlanishi kerak bo\'lgan email topilmadi.');
        }

        $request->validate([
            'email_code' => ['required', 'string', 'size:6', 'regex:/^\d{6}$/'],
        ], [
            'email_code.required' => '6 xonali tasdiqlash kodini kiriting.',
            'email_code.size' => 'Kod 6 ta raqamdan iborat bo\'lishi kerak.',
            'email_code.regex' => 'Kod faqat raqamlardan iborat bo\'lishi kerak.',
        ]);

        $tokenKey = 'email_verify:'.$user->id;
        $record = DB::table('password_reset_tokens')->where('phone', $tokenKey)->first();

        if (! $record || Carbon::parse($record->created_at)->addMinutes(self::EXPIRES_MINUTES)->isPast()) {
            DB::table('password_reset_tokens')->where('phone', $tokenKey)->delete();
            session()->forget('pending_profile_contact');

            return redirect()->route('profile.edit')->with('error', 'Tasdiqlash kodi muddati o\'tgan. Qaytadan urinib ko\'ring.');
        }

        if (! Hash::check($request->input('email_code'), $record->token)) {
            $attempts = $record->attempts + 1;

            if ($attempts >= self::MAX_ATTEMPTS) {
                DB::table('password_reset_tokens')->where('phone', $tokenKey)->delete();
                session()->forget('pending_profile_contact');

                return redirect()->route('profile.edit')
                    ->with('error', '5 marta noto\'g\'ri kod kiritildi. Xavfsizlik uchun so\'rov bekor qilindi.');
            }

            DB::table('password_reset_tokens')->where('phone', $tokenKey)->update(['attempts' => $attempts]);
            $remaining = self::MAX_ATTEMPTS - $attempts;

            return back()->withErrors([
                'email_code' => "Kod noto'g'ri. Qolgan urinishlar: {$remaining}.",
            ]);
        }

        $user->email = $pending['email'];
        $user->save();

        DB::table('password_reset_tokens')->where('phone', $tokenKey)->delete();
        session()->forget('pending_profile_contact');

        return redirect()->route('profile.edit')
            ->with('success', 'Email manzilingiz muvaffaqiyatli tasdiqlandi va saqlandi!');
    }

    /**
     * Parolni o'zgartirish uchun Email yoki SMS orqali 6 xonali kod yuborish
     */
    public function sendPasswordCode(Request $request, EskizSmsService $smsService): RedirectResponse
    {
        $user = $request->user();
        $channel = $request->input('channel', $user->email ? 'email' : 'phone');

        if ($channel === 'email' && ! $user->email) {
            return back()->with('error', 'Avval email manzilingizni kiriting va tasdiqlang yoki SMS orqali kod oling.');
        }

        $tokenKey = 'pwd_change:'.$user->id;
        $existing = DB::table('password_reset_tokens')->where('phone', $tokenKey)->first();

        if ($existing && $existing->created_at) {
            $availableAt = Carbon::parse($existing->created_at)->addSeconds(self::RESEND_COOLDOWN_SECONDS);
            if ($availableAt->isFuture()) {
                $secondsLeft = (int) ceil(now()->diffInSeconds($availableAt));

                return back()->with('error', "Iltimos, yangi kod so'rash uchun {$secondsLeft} soniya kuting.");
            }
        }

        $code = (string) random_int(100000, 999999);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['phone' => $tokenKey],
            [
                'token' => Hash::make($code),
                'attempts' => 0,
                'created_at' => now(),
            ]
        );

        if ($channel === 'email') {
            try {
                Mail::to($user->email)->send(new VerificationCodeMail(
                    code: $code,
                    userName: $user->name,
                    purpose: "Parolni o'zgartirish kodi",
                    description: 'Profilingiz parolini yangilash uchun quyidagi 6 xonali tasdiqlash kodini kiriting:',
                ));
                $sent = true;
                $target = $user->email;
            } catch (\Throwable $e) {
                Log::error('Profil parol email xatosi: '.$e->getMessage());
                $sent = false;
                $target = $user->email;
            }
        } else {
            $appName = config('app.name', 'RepetitorLMS');
            $sent = $smsService->send($user->phone, "{$appName}: Parolni o'zgartirish kodi: {$code}. Ushbu kodni hech kimga bermang!");
            $target = $user->phone;
        }

        if (! $sent) {
            DB::table('password_reset_tokens')->where('phone', $tokenKey)->delete();

            return back()->with('error', 'Tasdiqlash kodini yuborishda xatolik yuz berdi.');
        }

        session(['profile_password_otp_target' => $target]);

        return back()->with('success', "{$target} manziliga 6 xonali tasdiqlash kodi yuborildi.");
    }

    /**
     * 6 xonali OTP kodni tekshirib yangi parolni saqlash
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();
        $tokenKey = 'pwd_change:'.$user->id;

        $request->validate([
            'code' => ['required', 'string', 'size:6', 'regex:/^\d{6}$/'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'code.required' => '6 xonali tasdiqlash kodini kiriting.',
            'code.size' => 'Tasdiqlash kodi 6 ta raqamdan iborat bo\'lishi kerak.',
            'code.regex' => 'Tasdiqlash kodi faqat raqamlardan iborat bo\'lishi kerak.',
            'password.required' => 'Yangi parolni kiriting.',
            'password.confirmed' => 'Kiritilgan yangi parollar bir-biriga mos kelmadi.',
            'password.min' => 'Yangi parol kamida 8 ta belgidan iborat bo\'lsin.',
        ]);

        $record = DB::table('password_reset_tokens')->where('phone', $tokenKey)->first();

        if (! $record || Carbon::parse($record->created_at)->addMinutes(self::EXPIRES_MINUTES)->isPast()) {
            DB::table('password_reset_tokens')->where('phone', $tokenKey)->delete();
            session()->forget('profile_password_otp_target');

            return redirect()->route('profile.edit')
                ->with('error', 'Tasdiqlash kodi muddati o\'tgan. Qaytadan kod so\'rang.');
        }

        if (! Hash::check($request->input('code'), $record->token)) {
            $attempts = $record->attempts + 1;

            if ($attempts >= self::MAX_ATTEMPTS) {
                DB::table('password_reset_tokens')->where('phone', $tokenKey)->delete();
                session()->forget('profile_password_otp_target');

                return redirect()->route('profile.edit')
                    ->with('error', '5 marta noto\'g\'ri kod kiritildi. Xavfsizlik uchun kod bekor qilindi.');
            }

            DB::table('password_reset_tokens')->where('phone', $tokenKey)->update(['attempts' => $attempts]);
            $remaining = self::MAX_ATTEMPTS - $attempts;

            return back()->withErrors([
                'code' => "Tasdiqlash kodi noto'g'ri. Qolgan urinishlar: {$remaining}.",
            ]);
        }

        $user->forceFill([
            'password' => $request->input('password'),
        ])->save();

        DB::table('password_reset_tokens')->where('phone', $tokenKey)->delete();
        session()->forget('profile_password_otp_target');

        return redirect()->route('profile.edit')
            ->with('success', 'Parolingiz muvaffaqiyatli yangilandi!');
    }
}
