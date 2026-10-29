<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerificationCodeMail;
use App\Models\Setting;
use App\Models\User;
use App\Services\TelegramNotificationService;
use App\Support\Phone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    private const EXPIRES_MINUTES = 15;

    private const RESEND_COOLDOWN_SECONDS = 60;

    public function create()
    {
        return view('pages.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'phone' => Phone::normalize($request->input('phone')),
            'email' => $request->filled('email') ? mb_strtolower(trim((string) $request->input('email'))) : null,
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'phone' => ['required', 'unique:users,phone'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'name.required' => 'Ism va familiyani kiriting.',
            'name.min' => 'Ism kamida 3 ta belgidan iborat bo\'lsin.',
            'phone.required' => 'To\'g\'ri telefon raqam kiriting (masalan: +998 90 123 45 67).',
            'phone.unique' => 'Bu telefon raqam allaqachon ro\'yxatdan o\'tgan.',
            'email.required' => 'Email manzilni kiriting.',
            'email.email' => 'To\'g\'ri email manzil kiriting (masalan: ali@gmail.com).',
            'email.unique' => 'Bu email manzil allaqachon ro\'yxatdan o\'tgan.',
            'password.required' => 'Parolni kiriting.',
            'password.confirmed' => 'Parollar bir xil emas.',
            'password.min' => 'Parol kamida 8 ta belgidan iborat bo\'lsin.',
        ]);

        $tokenKey = 'reg:'.$data['email'];
        $telegramRequired = $this->isTelegramSubscriptionRequired();

        $existing = DB::table('password_reset_tokens')->where('phone', $tokenKey)->first();
        if (! $telegramRequired && $existing && $existing->created_at) {
            $availableAt = Carbon::parse($existing->created_at)->addSeconds(self::RESEND_COOLDOWN_SECONDS);
            if ($availableAt->isFuture()) {
                $secondsLeft = (int) ceil(now()->diffInSeconds($availableAt));

                return back()->withInput()->withErrors([
                    'email' => "Iltimos, yangi kod yuborish uchun {$secondsLeft} soniya kuting.",
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

        if (! $telegramRequired) {
            try {
                Mail::to($data['email'])->send(new VerificationCodeMail(
                    code: $code,
                    userName: $data['name'],
                    purpose: "Ro'yxatdan o'tishni tasdiqlash",
                    description: 'Hisobingizni faollashtirish uchun quyidagi 6 xonali tasdiqlash kodini saytga kiriting:',
                ));
            } catch (\Throwable $e) {
                Log::error('Ro\'yxatdan o\'tish email xatosi: '.$e->getMessage());
                DB::table('password_reset_tokens')->where('phone', $tokenKey)->delete();

                return back()->withInput()->with('error', 'Email manzilga tasdiqlash kodi yuborib bo\'lmadi. Manzilni tekshiring.');
            }
        }

        $telegramToken = Str::random(20);

        Cache::put('tg_reg_context:'.$telegramToken, [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
        ], now()->addMinutes(45));
        Cache::put('tg_latest_reg_token', $telegramToken, now()->addMinutes(45));

        session([
            'pending_registration' => [
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'telegram_token' => $telegramToken,
                'telegram_chat_id' => null,
                'channel_verified' => false,
            ],
        ]);

        if ($telegramRequired) {
            return redirect()->route('register.verify')
                ->with('success', 'Ma\'lumotlar qabul qilindi! Endi Telegram botga kirib, kanalga obuna bo\'ling va kodni oling.');
        }

        return redirect()->route('register.verify')
            ->with('success', "{$data['email']} manziliga 6 xonali tasdiqlash kodi yuborildi.");
    }

    public function showVerifyForm()
    {
        $pending = $this->syncPendingTelegramState();

        if (! $pending) {
            return redirect()->route('register')
                ->with('error', 'Avval ro\'yxatdan o\'tish ma\'lumotlarini kiriting.');
        }

        $telegramRequired = $this->isTelegramSubscriptionRequired();
        $botUsername = ltrim((string) Setting::get('telegram_bot_username', ''), '@');
        $channelUsername = ltrim((string) Setting::get('telegram_username', ''), '@');
        $telegramToken = $pending['telegram_token'] ?? '';

        $botStartLink = $botUsername !== ''
            ? "https://t.me/{$botUsername}?start=reg_{$telegramToken}"
            : null;
        $channelLink = $channelUsername !== ''
            ? "https://t.me/{$channelUsername}"
            : null;

        return view('pages.auth.verify-register', compact(
            'pending',
            'telegramRequired',
            'botUsername',
            'channelUsername',
            'botStartLink',
            'channelLink',
        ));
    }

    /**
     * Brauzerdan har 2.5 soniyada chaqirilib, Telegram botga kelgan `/start` va ` Obuna bo'ldim` tugmalarini jonli ishlaydi.
     */
    public function pollTelegramStatus(TelegramNotificationService $telegram): JsonResponse
    {
        $pending = session('pending_registration');

        if (! $pending || empty($pending['telegram_token'])) {
            return response()->json(['verified' => false, 'started' => false]);
        }

        $telegram->processBotUpdates();
        $pending = $this->syncPendingTelegramState();

        $token = (string) ($pending['telegram_token'] ?? '');
        $started = Cache::has('tg_start:'.$token);

        return response()->json([
            'started' => $started,
            'verified' => ! empty($pending['channel_verified']),
        ]);
    }

    public function checkTelegram(TelegramNotificationService $telegram): RedirectResponse
    {
        $pending = session('pending_registration');

        if (! $pending || empty($pending['telegram_token'])) {
            return redirect()->route('register')
                ->with('error', 'Sessiya muddati tugagan. Qaytadan ro\'yxatdan o\'ting.');
        }

        $regToken = (string) $pending['telegram_token'];
        $chatId = $telegram->resolveUserChatIdByStartToken($regToken);

        $pending = $this->syncPendingTelegramState();
        if (! empty($pending['channel_verified'])) {
            return back()->with('success', 'Kanalga obuna tasdiqlandi! 6 xonali kod Telegramingizga yuborildi.');
        }

        if (! $chatId) {
            return back()->with('error', 'Siz hali Telegram botga kirib START tugmasini bosmadingiz. 1-qadamdagi tugmani bosib botga Start bosing.');
        }

        Cache::put('tg_reg_context:'.$regToken, [
            'name' => $pending['name'],
            'email' => $pending['email'],
            'phone' => $pending['phone'],
        ], now()->addMinutes(45));

        $result = $telegram->verifySubscriptionAndSendCode($chatId, $regToken);

        if (! $result['ok']) {
            return back()->with('error', $result['message']);
        }

        $pending['telegram_chat_id'] = $chatId;
        $pending['channel_verified'] = true;
        session(['pending_registration' => $pending]);

        return back()->with('success', $result['message']);
    }

    public function verify(Request $request): RedirectResponse
    {
        $pending = $this->syncPendingTelegramState();

        if (! $pending) {
            return redirect()->route('register')
                ->with('error', 'Sessiya muddati tugagan. Qaytadan ro\'yxatdan o\'ting.');
        }

        if ($this->isTelegramSubscriptionRequired() && empty($pending['channel_verified'])) {
            return back()->withErrors([
                'code' => 'Ro\'yxatdan o\'tishni yakunlash uchun avval Telegram bot va rasmiy kanalga a\'zo bo\'lib, obunani tasdiqlang.',
            ]);
        }

        $request->validate([
            'code' => ['required', 'string', 'size:6', 'regex:/^\d{6}$/'],
        ], [
            'code.required' => '6 xonali tasdiqlash kodini kiriting.',
            'code.size' => 'Tasdiqlash kodi 6 ta raqamdan iborat bo\'lishi kerak.',
            'code.regex' => 'Tasdiqlash kodi faqat raqamlardan iborat bo\'lishi kerak.',
        ]);

        $tokenKey = 'reg:'.$pending['email'];
        $record = DB::table('password_reset_tokens')->where('phone', $tokenKey)->first();

        if (! $record || Carbon::parse($record->created_at)->addMinutes(self::EXPIRES_MINUTES)->isPast()) {
            DB::table('password_reset_tokens')->where('phone', $tokenKey)->delete();

            return back()->withErrors([
                'code' => 'Tasdiqlash kodi muddati o\'tgan. "Qayta yuborish" tugmasini bosing.',
            ]);
        }

        if ($record->attempts >= self::MAX_ATTEMPTS) {
            DB::table('password_reset_tokens')->where('phone', $tokenKey)->delete();
            session()->forget('pending_registration');

            return redirect()->route('register')
                ->with('error', 'Ko\'p marta noto\'g\'ri kod kiritildi. Qaytadan ro\'yxatdan o\'ting.');
        }

        if (! Hash::check($request->input('code'), $record->token)) {
            $attempts = $record->attempts + 1;

            if ($attempts >= self::MAX_ATTEMPTS) {
                DB::table('password_reset_tokens')->where('phone', $tokenKey)->delete();
                session()->forget('pending_registration');

                return redirect()->route('register')
                    ->with('error', '5 marta noto\'g\'ri kod kiritildi. Xavfsizlik uchun so\'rov bekor qilindi.');
            }

            DB::table('password_reset_tokens')
                ->where('phone', $tokenKey)
                ->update(['attempts' => $attempts]);

            $remaining = self::MAX_ATTEMPTS - $attempts;

            return back()->withErrors([
                'code' => "Tasdiqlash kodi noto'g'ri. Qolgan urinishlar: {$remaining}.",
            ]);
        }

        if (User::where('phone', $pending['phone'])->orWhere('email', $pending['email'])->exists()) {
            DB::table('password_reset_tokens')->where('phone', $tokenKey)->delete();
            session()->forget('pending_registration');

            return redirect()->route('login')
                ->with('error', 'Ushbu hisob allaqachon yaratilgan. Iltimos, tizimga kiring.');
        }

        $user = User::create([
            'name' => $pending['name'],
            'phone' => $pending['phone'],
            'email' => $pending['email'],
            'telegram_chat_id' => $pending['telegram_chat_id'] ?? null,
            'password' => $pending['password'],
        ]);

        if (! empty($pending['telegram_token'])) {
            Cache::forget('tg_verified:'.$pending['telegram_token']);
            Cache::forget('tg_reg_context:'.$pending['telegram_token']);
            Cache::forget('tg_start:'.$pending['telegram_token']);
        }

        DB::table('password_reset_tokens')->where('phone', $tokenKey)->delete();
        session()->forget('pending_registration');

        Auth::login($user);
        $request->session()->regenerate();
        $user->forceFill(['current_session_id' => $request->session()->getId()])->save();

        return redirect()->intended(route('courses.index'))
            ->with('success', "Xush kelibsiz, {$user->name}! Hisobingiz muvaffaqiyatli tasdiqlandi.");
    }

    public function resend(): RedirectResponse
    {
        $pending = session('pending_registration');

        if (! $pending) {
            return redirect()->route('register');
        }

        $tokenKey = 'reg:'.$pending['email'];
        $existing = DB::table('password_reset_tokens')->where('phone', $tokenKey)->first();

        if ($existing && $existing->created_at) {
            $availableAt = Carbon::parse($existing->created_at)->addSeconds(self::RESEND_COOLDOWN_SECONDS);
            if ($availableAt->isFuture()) {
                $secondsLeft = (int) ceil(now()->diffInSeconds($availableAt));

                return back()->withErrors([
                    'code' => "Yangi kod so'rash uchun {$secondsLeft} soniya kuting.",
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
            Mail::to($pending['email'])->send(new VerificationCodeMail(
                code: $code,
                userName: $pending['name'],
                purpose: "Ro'yxatdan o'tishni tasdiqlash",
                description: 'Hisobingizni faollashtirish uchun quyidagi 6 xonali tasdiqlash kodini saytga kiriting:',
            ));
        } catch (\Throwable $e) {
            Log::error('Ro\'yxatdan o\'tish qayta email xatosi: '.$e->getMessage());

            return back()->with('error', 'Kod yuborishda xatolik yuz berdi.');
        }

        return back()->with('success', "{$pending['email']} manziliga yangi tasdiqlash kodi yuborildi.");
    }

    /**
     * @return array<string, mixed>|null
     */
    private function syncPendingTelegramState(): ?array
    {
        $pending = session('pending_registration');

        if (! $pending) {
            return null;
        }

        $regToken = (string) ($pending['telegram_token'] ?? '');
        if ($regToken !== '') {
            $verifiedData = Cache::get('tg_verified:'.$regToken);
            if (is_array($verifiedData) && ! empty($verifiedData['chat_id'])) {
                $pending['telegram_chat_id'] = (string) $verifiedData['chat_id'];
                $pending['channel_verified'] = true;
                session(['pending_registration' => $pending]);
            }
        }

        return $pending;
    }

    private function isTelegramSubscriptionRequired(): bool
    {
        return Setting::get('require_telegram_subscription', '0') === '1'
            && ! empty(Setting::getSecret('telegram_bot_token', config('services.telegram.bot_token')))
            && ! empty(Setting::get('telegram_bot_username'))
            && ! empty(Setting::get('telegram_username'));
    }
}
