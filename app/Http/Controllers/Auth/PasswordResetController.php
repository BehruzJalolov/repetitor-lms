<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetCodeMail;
use App\Models\User;
use App\Services\EskizSmsService;
use App\Services\TelegramNotificationService;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class PasswordResetController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    private const EXPIRES_MINUTES = 15;

    private const RESEND_COOLDOWN_SECONDS = 60;

    /**
     * Telefon raqam yoki email kiritish sahifasi
     */
    public function showRequestForm()
    {
        return view('pages.auth.forgot-password');
    }

    /**
     * SMS, Email yoki Telegram Bot orqali 6 xonali tasdiqlash kodi yuborish
     */
    public function sendResetCode(Request $request, EskizSmsService $smsService, TelegramNotificationService $telegram): RedirectResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'max:255'],
        ], [
            'phone.required' => 'Telefon raqam yoki email manzilni kiriting.',
        ]);

        $rawInput = trim((string) $request->input('phone'));
        $isEmail = str_contains($rawInput, '@');

        if ($isEmail) {
            $identifier = filter_var(Str::lower($rawInput), FILTER_VALIDATE_EMAIL) ?: null;
            if (! $identifier) {
                return back()->withInput()->withErrors([
                    'phone' => 'Email manzil formati noto\'g\'ri (masalan: ali@gmail.com).',
                ]);
            }
            $user = User::where('email', $identifier)->first();
            if (! $user) {
                return back()->withInput()->withErrors([
                    'phone' => 'Bu email manzil bilan hisob topilmadi.',
                ]);
            }
        } else {
            $identifier = Phone::normalize($rawInput);
            if (! $identifier) {
                return back()->withInput()->withErrors([
                    'phone' => 'Telefon raqam formati noto\'g\'ri (+998 90 123 45 67).',
                ]);
            }
            $user = User::where('phone', $identifier)->first();
            if (! $user) {
                return back()->withInput()->withErrors([
                    'phone' => 'Bu telefon raqam bilan hisob topilmadi.',
                ]);
            }
        }

        // Qayta kod so'rash uchun 60 soniyalik cheklov (spam himoyasi)
        $existing = DB::table('password_reset_tokens')->where('phone', $identifier)->first();
        if ($existing && $existing->created_at) {
            $availableAt = Carbon::parse($existing->created_at)->addSeconds(self::RESEND_COOLDOWN_SECONDS);
            if ($availableAt->isFuture()) {
                $secondsLeft = (int) ceil(now()->diffInSeconds($availableAt));

                return back()->withInput()->withErrors([
                    'phone' => "Iltimos, yangi kod so'rash uchun {$secondsLeft} soniya kuting.",
                ]);
            }
        }

        $code = (string) random_int(100000, 999999);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['phone' => $identifier],
            [
                'token' => Hash::make($code),
                'attempts' => 0,
                'created_at' => now(),
            ]
        );

        if ($isEmail) {
            try {
                Mail::to($identifier)->send(new PasswordResetCodeMail($code, $user->name));
                $sent = true;
            } catch (\Throwable $e) {
                Log::error('Parolni tiklash email xatosi: '.$e->getMessage());
                $sent = false;
            }
        } else {
            $appName = config('app.name', 'RepetitorLMS');
            $smsMessage = "{$appName}: Parolni tiklash kodi: {$code}. Ushbu kodni hech kimga bermang!";
            $sent = $smsService->send($identifier, $smsMessage);
        }

        // Agar foydalanuvchi Telegram botga ulangan bo'lsa, kodni Telegram orqali ham yuboramiz
        if ($user->telegram_chat_id) {
            $tgSent = $telegram->sendToChat(
                (string) $user->telegram_chat_id,
                "🔐 <b>Parolni tiklash kodi:</b> <code>{$code}</code>\n\n"
                .'⚠️ Ushbu kodni hech kimga bermang! Kod 15 daqiqa davomida amal qiladi.'
            );
            $sent = $sent || $tgSent;
        }

        if (! $sent) {
            DB::table('password_reset_tokens')->where('phone', $identifier)->delete();

            return back()->withInput()->with(
                'error',
                $isEmail
                    ? 'Email yuborishda xatolik yuz berdi. Iltimos, keyinroq qayta urinib ko\'ring.'
                    : 'SMS yuborishda xatolik yuz berdi. Iltimos, keyinroq qayta urinib ko\'ring.'
            );
        }

        session(['password_reset_phone' => $identifier]);

        $channelLabel = $isEmail ? 'email manziliga' : 'raqamiga SMS orqali';

        return redirect()->route('password.reset')
            ->with('success', "{$identifier} {$channelLabel} 6 xonali tasdiqlash kodi yuborildi.");
    }

    /**
     * Kod va yangi parol kiritish sahifasi
     */
    public function showResetForm()
    {
        $phone = session('password_reset_phone');

        if (! $phone) {
            return redirect()->route('password.request')
                ->with('error', 'Avval telefon raqamingiz yoki email manzilingizni kiriting.');
        }

        return view('pages.auth.reset-password', compact('phone'));
    }

    /**
     * Yangi parolni saqlash
     */
    public function resetPassword(Request $request): RedirectResponse
    {
        $rawInput = trim((string) $request->input('phone'));
        $isEmail = str_contains($rawInput, '@');
        $identifier = $isEmail
            ? (filter_var(Str::lower($rawInput), FILTER_VALIDATE_EMAIL) ?: null)
            : Phone::normalize($rawInput);

        $request->merge([
            'phone' => $identifier,
        ]);

        $request->validate([
            'phone' => ['required', 'string'],
            'code' => ['required', 'string', 'size:6', 'regex:/^\d{6}$/'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'phone.required' => 'Telefon raqam yoki email ko\'rsatilmadi.',
            'code.required' => '6 xonali tasdiqlash kodini kiriting.',
            'code.size' => 'Tasdiqlash kodi 6 ta raqamdan iborat bo\'lishi kerak.',
            'code.regex' => 'Tasdiqlash kodi faqat raqamlardan iborat bo\'lishi kerak.',
            'password.required' => 'Yangi parolni kiriting.',
            'password.confirmed' => 'Kiritilgan yangi parollar bir-biriga mos kelmadi.',
            'password.min' => 'Yangi parol kamida 8 ta belgidan iborat bo\'lsin.',
        ]);

        $code = $request->input('code');

        $record = DB::table('password_reset_tokens')
            ->where('phone', $identifier)
            ->first();

        if (! $record || Carbon::parse($record->created_at)->addMinutes(self::EXPIRES_MINUTES)->isPast()) {
            DB::table('password_reset_tokens')->where('phone', $identifier)->delete();

            return back()->withInput()->withErrors([
                'code' => 'Tasdiqlash kodi muddati o\'tgan yoki topilmadi. Qaytadan kod so\'rang.',
            ]);
        }

        if ($record->attempts >= self::MAX_ATTEMPTS) {
            DB::table('password_reset_tokens')->where('phone', $identifier)->delete();
            session()->forget('password_reset_phone');

            return redirect()->route('password.request')
                ->with('error', 'Ko\'p marta noto\'g\'ri kod kiritildi. Xavfsizlik uchun kod bekor qilindi, qaytadan kod so\'rang.');
        }

        if (! Hash::check($code, $record->token)) {
            $attempts = $record->attempts + 1;

            if ($attempts >= self::MAX_ATTEMPTS) {
                DB::table('password_reset_tokens')->where('phone', $identifier)->delete();
                session()->forget('password_reset_phone');

                return redirect()->route('password.request')
                    ->with('error', '5 marta noto\'g\'ri kod kiritildi. Xavfsizlik uchun kod bekor qilindi, yangi kod so\'rang.');
            }

            DB::table('password_reset_tokens')
                ->where('phone', $identifier)
                ->update(['attempts' => $attempts]);

            $remaining = self::MAX_ATTEMPTS - $attempts;

            return back()->withInput()->withErrors([
                'code' => "Tasdiqlash kodi noto'g'ri. Qolgan urinishlar: {$remaining}.",
            ]);
        }

        $user = $isEmail
            ? User::where('email', $identifier)->firstOrFail()
            : User::where('phone', $identifier)->firstOrFail();

        $user->forceFill([
            'password' => $request->input('password'),
            'current_session_id' => null,
            'remember_token' => Str::random(60),
        ])->save();

        DB::table('sessions')->where('user_id', $user->id)->delete();
        DB::table('password_reset_tokens')->where('phone', $identifier)->delete();
        session()->forget('password_reset_phone');

        return redirect()->route('login')
            ->with('success', 'Parolingiz muvaffaqiyatli yangilandi! Yangi parol bilan kiring.');
    }
}
