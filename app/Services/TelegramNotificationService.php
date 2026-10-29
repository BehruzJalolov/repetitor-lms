<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramNotificationService
{
    public function getToken(): ?string
    {
        $token = Setting::getSecret('telegram_bot_token', config('services.telegram.bot_token'));

        return is_string($token) && trim($token) !== '' ? trim($token) : null;
    }

    public function send(string $message): bool
    {
        $chatId = Setting::get('telegram_chat_id', config('services.telegram.chat_id'));

        if (empty($chatId)) {
            Log::info("Telegram bildirishnoma (Lokal): {$message}");

            return true;
        }

        return $this->sendToChat((string) $chatId, $message);
    }

    /**
     * @param  array<int, array<int, array<string, string>>>|null  $inlineKeyboard
     */
    public function sendToChat(string|int $chatId, string $message, ?array $inlineKeyboard = null): bool
    {
        $token = $this->getToken();

        if ($token === null) {
            Log::info("Telegram xabar [{$chatId}] (Lokal): {$message}");

            return true;
        }

        $payload = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ];

        if ($inlineKeyboard !== null) {
            $payload['reply_markup'] = json_encode([
                'inline_keyboard' => $inlineKeyboard,
            ]);
        }

        try {
            $response = Http::timeout(8)->post("https://api.telegram.org/bot{$token}/sendMessage", $payload);

            if (! $response->successful()) {
                Log::warning('Telegram xabar yuborilmadi: '.$response->body());

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Telegram exception: '.$e->getMessage());

            return false;
        }
    }

    public function answerCallbackQuery(string $callbackQueryId, string $text, bool $showAlert = false): void
    {
        $token = $this->getToken();

        if (! $token) {
            return;
        }

        try {
            Http::timeout(6)->post("https://api.telegram.org/bot{$token}/answerCallbackQuery", [
                'callback_query_id' => $callbackQueryId,
                'text' => $text,
                'show_alert' => $showAlert,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Telegram answerCallbackQuery xatosi: '.$e->getMessage());
        }
    }

    public function fetchBotUsername(?string $customToken = null): ?string
    {
        $token = $customToken ?: $this->getToken();

        if (! $token) {
            return null;
        }

        try {
            $response = Http::timeout(6)->get("https://api.telegram.org/bot{$token}/getMe");

            if ($response->successful() && $response->json('ok')) {
                $username = $response->json('result.username');

                return $username ? '@'.ltrim((string) $username, '@') : null;
            }
        } catch (\Throwable $e) {
            Log::warning('Telegram getMe xatosi: '.$e->getMessage());
        }

        return null;
    }

    /**
     * Telegram Bot ga kelgan yangi xabarlar (`/start`) va tugma bosishlarni (`callback_query`) qayta ishlaydi.
     */
    public function processBotUpdates(): void
    {
        $token = $this->getToken();

        if (! $token) {
            return;
        }

        $offset = (int) Cache::get('tg_last_update_offset', 0);

        try {
            $params = [
                'limit' => 50,
                'allowed_updates' => json_encode(['message', 'callback_query']),
            ];

            if ($offset > 0) {
                $params['offset'] = $offset;
            }

            $response = Http::timeout(8)->get("https://api.telegram.org/bot{$token}/getUpdates", $params);

            if ($response->status() === 409 && str_contains(strtolower($response->body()), 'webhook')) {
                Http::timeout(6)->post("https://api.telegram.org/bot{$token}/deleteWebhook");
                $response = Http::timeout(8)->get("https://api.telegram.org/bot{$token}/getUpdates", $params);
            }

            if (! $response->successful() || ! $response->json('ok')) {
                return;
            }

            $updates = $response->json('result', []);

            foreach ($updates as $update) {
                $updateId = (int) ($update['update_id'] ?? 0);
                if ($updateId > 0) {
                    Cache::put('tg_last_update_offset', $updateId + 1, now()->addDays(7));
                }

                $this->handleSingleUpdate($update);
            }
        } catch (\Throwable $e) {
            Log::error('Telegram processBotUpdates xatosi: '.$e->getMessage());
        }
    }

    /**
     * @param  array<string, mixed>  $update
     */
    public function handleSingleUpdate(array $update): void
    {
        if (isset($update['callback_query'])) {
            $this->handleCallbackQuery($update['callback_query']);

            return;
        }

        if (isset($update['message'])) {
            $this->handleIncomingMessage($update['message']);
        }
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function handleIncomingMessage(array $message): void
    {
        $chatId = data_get($message, 'chat.id') ?? data_get($message, 'from.id');
        $text = trim((string) data_get($message, 'text', ''));
        $firstName = e((string) data_get($message, 'from.first_name', 'O\'quvchi'));

        if (! $chatId || $text === '') {
            return;
        }

        if (str_starts_with($text, '/start')) {
            $parts = preg_split('/\s+/', $text, 2);
            $payload = $parts[1] ?? '';
            $regToken = '';

            if (str_starts_with($payload, 'reg_')) {
                $regToken = substr($payload, 4);
            }

            if ($regToken === '') {
                $regToken = (string) Cache::get('tg_chat_reg_token:'.$chatId, Cache::get('tg_latest_reg_token', ''));
            }

            if ($regToken !== '') {
                Cache::put('tg_start:'.$regToken, (string) $chatId, now()->addMinutes(45));
                Cache::put('tg_chat_reg_token:'.$chatId, $regToken, now()->addMinutes(45));
            }

            $this->sendRegistrationMenu((string) $chatId, $firstName, $regToken);

            return;
        }

        if ($text === '/code' || $text === '🔐 Kod olish') {
            $regToken = (string) Cache::get('tg_chat_reg_token:'.$chatId, Cache::get('tg_latest_reg_token', ''));
            $this->verifySubscriptionAndSendCode((string) $chatId, $regToken);
        }
    }

    /**
     * @param  array<string, mixed>  $callbackQuery
     */
    private function handleCallbackQuery(array $callbackQuery): void
    {
        $callbackId = (string) ($callbackQuery['id'] ?? '');
        $data = (string) ($callbackQuery['data'] ?? '');
        $chatId = (string) (data_get($callbackQuery, 'message.chat.id') ?? data_get($callbackQuery, 'from.id'));

        if ($chatId === '') {
            return;
        }

        if (str_starts_with($data, 'verify_sub:')) {
            $regToken = substr($data, 11);
            if ($regToken === '') {
                $regToken = (string) Cache::get('tg_chat_reg_token:'.$chatId, Cache::get('tg_latest_reg_token', ''));
            }

            if ($regToken !== '') {
                Cache::put('tg_start:'.$regToken, $chatId, now()->addMinutes(45));
                Cache::put('tg_chat_reg_token:'.$chatId, $regToken, now()->addMinutes(45));
            }

            $this->verifySubscriptionAndSendCode($chatId, $regToken, $callbackId);

            return;
        }

        if ($data === 'info') {
            $siteName = e((string) Setting::get('site_name', 'RepetitorLMS'));
            $siteDesc = e((string) Setting::get('site_description', 'Tajribali repetitor bilan onlayn video kurslar platformasi.'));
            $coursesCount = Course::where('is_active', true)->count();

            $this->answerCallbackQuery($callbackId, "ℹ️ {$siteName} haqida ma'lumot");
            $this->sendToChat(
                $chatId,
                "🎓 <b>{$siteName}</b>\n\n"
                ."{$siteDesc}\n\n"
                ."📚 Faol kurslar soni: <b>{$coursesCount} ta</b>\n"
                .'🏅 Har bir kurs yakunida maxsus <b>Sertifikat</b> beriladi!',
                $this->buildDefaultInlineButtons((string) Cache::get('tg_chat_reg_token:'.$chatId, ''))
            );

            return;
        }

        if ($data === 'contact') {
            $phone = e((string) Setting::get('contact_phone', config('admin.phone', '+998 90 123 45 67')));
            $tgUser = e((string) Setting::get('telegram_username', '@repetitor_uz'));
            $address = e((string) Setting::get('contact_address', 'Toshkent shahri'));

            $this->answerCallbackQuery($callbackId, '📞 Bog\'lanish ma\'lumotlari');
            $this->sendToChat(
                $chatId,
                "📞 <b>Biz bilan bog'lanish:</b>\n\n"
                ."☎️ Telefon: <b>{$phone}</b>\n"
                ."✈️ Telegram: {$tgUser}\n"
                ."📍 Manzil: {$address}",
                $this->buildDefaultInlineButtons((string) Cache::get('tg_chat_reg_token:'.$chatId, ''))
            );
        }
    }

    public function sendRegistrationMenu(string $chatId, string $firstName, string $regToken = ''): void
    {
        $siteName = e((string) Setting::get('site_name', 'RepetitorLMS'));
        $channel = trim((string) Setting::get('telegram_username', config('services.telegram.channel_username', '@ITblockbehruz')));
        $cleanChannel = ltrim($channel, '@');
        $context = $regToken !== '' ? Cache::get('tg_reg_context:'.$regToken) : null;
        $studentName = is_array($context) && ! empty($context['name']) ? e((string) $context['name']) : $firstName;

        $text = "👋 Assalomu alaykum, <b>{$studentName}</b>!\n"
            ."🎓 <b>{$siteName}</b> rasmiy botiga xush kelibsiz.\n\n";

        if ($cleanChannel !== '') {
            $text .= "Ro'yxatdan o'tish va <b>6 xonali tasdiqlash kodini</b> olish uchun:\n"
                ."1️⃣ Quyidagi <b>«📢 Kanalga obuna bo'lish»</b> tugmasini bosib rasmiy kanalimizga a'zo bo'ling.\n"
                ."2️⃣ So'ngra <b>«✅ Obuna bo'ldim (Kodni olish)»</b> tugmasini bosing!";
        } else {
            $text .= '6 xonali tasdiqlash kodini olish uchun quyidagi <b>«🔐 Tasdiqlash kodini olish»</b> tugmasini bosing:';
        }

        $this->sendToChat($chatId, $text, $this->buildDefaultInlineButtons($regToken));
    }

    /**
     * @return array<int, array<int, array<string, string>>>
     */
    private function buildDefaultInlineButtons(string $regToken = ''): array
    {
        $channel = trim((string) Setting::get('telegram_username', config('services.telegram.channel_username', '@ITblockbehruz')));
        $cleanChannel = ltrim($channel, '@');
        $buttons = [];

        if ($cleanChannel !== '') {
            $buttons[] = [
                [
                    'text' => "📢 1. Kanalga obuna bo'lish (@{$cleanChannel})",
                    'url' => "https://t.me/{$cleanChannel}",
                ],
            ];
            $buttons[] = [
                [
                    'text' => '✅ 2. Obuna bo\'ldim (Kodni olish)',
                    'callback_data' => 'verify_sub:'.$regToken,
                ],
            ];
        } else {
            $buttons[] = [
                [
                    'text' => '🔐 Tasdiqlash kodini olish',
                    'callback_data' => 'verify_sub:'.$regToken,
                ],
            ];
        }

        $buttons[] = [
            [
                'text' => 'ℹ️ Markaz haqida',
                'callback_data' => 'info',
            ],
            [
                'text' => '📞 Bog\'lanish',
                'callback_data' => 'contact',
            ],
        ];

        return $buttons;
    }

    /**
     * Kanalga a'zolikni tekshirib, a'zo bo'lsa 6 xonali kodni Telegram botning o'zida yuboradi.
     *
     * @return array{ok: bool, message: string, chat_id?: string, code?: string}
     */
    public function verifySubscriptionAndSendCode(string $chatId, string $regToken, ?string $callbackId = null): array
    {
        $channel = trim((string) Setting::get('telegram_username', config('services.telegram.channel_username', '@ITblockbehruz')));
        $formattedChannel = $channel !== '' ? (str_starts_with($channel, '@') ? $channel : '@'.$channel) : '';

        if ($formattedChannel !== '') {
            $membership = $this->checkChannelMembership($chatId, $formattedChannel);

            if (! $membership['subscribed']) {
                if ($membership['reason'] === 'bot_not_admin') {
                    $errMsg = "⚠️ Bot hali {$formattedChannel} kanaliga Admin qilinmagan! Iltimos, botni kanalga Admin qilib qo'shing.";
                    if ($callbackId) {
                        $this->answerCallbackQuery($callbackId, $errMsg, true);
                    }
                    $this->sendToChat(
                        $chatId,
                        "⚠️ <b>Sozlama xatosi:</b> Ushbu bot <b>{$formattedChannel}</b> kanalida Admin emas.\n\n"
                        ."Kanal a'zolarini tekshira olishi uchun botni kanalga <b>Admin</b> qilib qo'shing va qayta urinib ko'ring.",
                        $this->buildDefaultInlineButtons($regToken)
                    );

                    return ['ok' => false, 'message' => $errMsg];
                }

                $alertText = "❌ Siz hali {$formattedChannel} kanaliga obuna bo'lmadingiz!\n\nAvval 1-tugmani bosib kanalga a'zo bo'ling, so'ngra «✅ Obuna bo'ldim» tugmasini bosing.";
                if ($callbackId) {
                    $this->answerCallbackQuery($callbackId, $alertText, true);
                }

                $this->sendToChat(
                    $chatId,
                    "⚠️ <b>Kanalga obuna topilmadi!</b>\n\n"
                    ."Ro'yxatdan o'tish kodini olish uchun avval <b>{$formattedChannel}</b> kanalimizga obuna bo'lishingiz shart.\n\n"
                    ."👇 Pastdagi 1-tugma orqali kanalga kiring va <b>Obuna bo'lish (Join)</b> tugmasini bosing, keyin 2-tugmani bosing:",
                    $this->buildDefaultInlineButtons($regToken)
                );

                return [
                    'ok' => false,
                    'message' => "Siz hali {$formattedChannel} kanaliga obuna bo'lmagansiz! Botdagi tugma orqali kanalga a'zo bo'ling va «✅ Obuna bo'ldim» tugmasini bosing.",
                ];
            }
        }

        $context = $regToken !== '' ? Cache::get('tg_reg_context:'.$regToken) : null;
        $email = is_array($context) ? ($context['email'] ?? null) : null;
        $studentName = is_array($context) ? ($context['name'] ?? 'O\'quvchi') : 'O\'quvchi';

        $code = (string) random_int(100000, 999999);

        if ($email) {
            DB::table('password_reset_tokens')->updateOrInsert(
                ['phone' => 'reg:'.$email],
                [
                    'token' => Hash::make($code),
                    'attempts' => 0,
                    'created_at' => now(),
                ]
            );
        }

        if ($regToken !== '') {
            Cache::put('tg_verified:'.$regToken, [
                'chat_id' => $chatId,
                'code' => $code,
                'verified_at' => now()->toIso8601String(),
            ], now()->addMinutes(45));
        }

        if ($callbackId) {
            $this->answerCallbackQuery($callbackId, '✅ Obuna tasdiqlandi! Sizga 6 xonali kod yuborildi.');
        }

        $siteName = e((string) Setting::get('site_name', 'RepetitorLMS'));

        $this->sendToChat(
            $chatId,
            '🎉 <b>Tabriklaymiz, '.e((string) $studentName)."!</b>\n"
            ."✅ Kanalga obuna bo'lganingiz tasdiqlandi.\n\n"
            ."🎓 Platforma: <b>{$siteName}</b>\n"
            ."🔐 Sizning 6 xonali tasdiqlash kodingiz:\n\n"
            ."<code>{$code}</code>\n\n"
            .'👆 <i>Kodning ustiga bossangiz nusxalanadi. Endi saytga qaytib, ushbu kodni kiriting!</i>',
            [
                [
                    [
                        'text' => '🔄 Yangi kod olish',
                        'callback_data' => 'verify_sub:'.$regToken,
                    ],
                ],
                [
                    [
                        'text' => 'ℹ️ Markaz haqida',
                        'callback_data' => 'info',
                    ],
                    [
                        'text' => '📞 Bog\'lanish',
                        'callback_data' => 'contact',
                    ],
                ],
            ]
        );

        return [
            'ok' => true,
            'message' => 'Kanalga obuna tasdiqlandi! 6 xonali kod Telegramingizga yuborildi.',
            'chat_id' => $chatId,
            'code' => $code,
        ];
    }

    /**
     * Foydalanuvchi botga `/start reg_TOKEN` yuborgan bo'lsa, uning Telegram chat_id raqamini topadi.
     */
    public function resolveUserChatIdByStartToken(string $startToken): ?string
    {
        $cacheKey = 'tg_start:'.$startToken;
        $cached = Cache::get($cacheKey);

        if ($cached) {
            return (string) $cached;
        }

        $this->processBotUpdates();

        $resolved = Cache::get($cacheKey);

        return $resolved ? (string) $resolved : null;
    }

    /**
     * @return array{subscribed: bool, reason: string|null}
     */
    public function checkChannelMembership(string|int $userChatId, string $channel): array
    {
        $token = $this->getToken();
        $channel = trim($channel);

        if (! $token || $channel === '') {
            return ['subscribed' => true, 'reason' => null];
        }

        if (! str_starts_with($channel, '@') && ! str_starts_with($channel, '-')) {
            $channel = '@'.$channel;
        }

        try {
            $response = Http::timeout(8)->get("https://api.telegram.org/bot{$token}/getChatMember", [
                'chat_id' => $channel,
                'user_id' => $userChatId,
            ]);

            if (! $response->successful() || ! $response->json('ok')) {
                $desc = strtolower((string) $response->json('description', ''));
                Log::warning('Telegram getChatMember muvaffaqiyatsiz: '.$response->body());

                if (
                    str_contains($desc, 'admin')
                    || str_contains($desc, 'inaccessible')
                    || str_contains($desc, 'chat not found')
                    || str_contains($desc, 'forbidden')
                ) {
                    return ['subscribed' => false, 'reason' => 'bot_not_admin'];
                }

                return ['subscribed' => false, 'reason' => 'not_member'];
            }

            $status = (string) $response->json('result.status', '');

            if (in_array($status, ['creator', 'administrator', 'member'], true)) {
                return ['subscribed' => true, 'reason' => null];
            }

            if ($status === 'restricted' && $response->json('result.is_member') === true) {
                return ['subscribed' => true, 'reason' => null];
            }

            return ['subscribed' => false, 'reason' => 'not_member'];
        } catch (\Throwable $e) {
            Log::error('Telegram getChatMember exception: '.$e->getMessage());

            return ['subscribed' => false, 'reason' => 'not_member'];
        }
    }

    /**
     * Foydalanuvchi ko'rsatilgan Telegram kanaliga a'zo ekanligini (`getChatMember`) tekshiradi.
     */
    public function isSubscribedToChannel(string|int $userChatId, string $channel): bool
    {
        return $this->checkChannelMembership($userChatId, $channel)['subscribed'];
    }
}
