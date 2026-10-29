<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\TelegramNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    private const MASKED_SECRET = '••••••••••••••••';

    public function edit(): View
    {
        $hasBotToken = ! empty(Setting::getSecret('telegram_bot_token', config('services.telegram.bot_token', '')));

        $settings = [
            'site_name' => Setting::get('site_name', 'RepetitorLMS'),
            'site_description' => Setting::get('site_description', 'Tajribali repetitor bilan onlayn o\'qing. Video darslar, konspektlar va testlar — barchasi bir joyda, istalgan vaqtda va istalgan qurilmada.'),
            'contact_phone' => Setting::get('contact_phone', config('admin.phone', '+998 90 123 45 67')),
            'telegram_username' => Setting::get('telegram_username', '@repetitor_uz'),
            'contact_address' => Setting::get('contact_address', 'Toshkent shahri'),
            'card_number' => Setting::get('card_number', config('admin.card_number', '8600 0000 0000 0000')),
            'card_holder' => Setting::get('card_holder', config('admin.card_holder', 'Repetitor')),
            'has_bot_token' => $hasBotToken,
            'telegram_bot_username' => Setting::get('telegram_bot_username', ''),
            'telegram_chat_id' => Setting::get('telegram_chat_id', config('services.telegram.chat_id', '')),
            'require_telegram_subscription' => Setting::get('require_telegram_subscription', '0') === '1',
            'blocked_words' => Setting::get('blocked_words', ''),
        ];

        return view('admin.settings.edit', compact('settings'));
    }

    public function update(Request $request, TelegramNotificationService $telegram): RedirectResponse
    {
        $validated = $request->validate([
            'site_name' => ['required', 'string', 'max:80'],
            'site_description' => ['nullable', 'string', 'max:500'],
            'contact_phone' => ['required', 'string', 'max:40'],
            'telegram_username' => ['nullable', 'string', 'max:80'],
            'contact_address' => ['nullable', 'string', 'max:150'],
            'card_number' => ['required', 'string', 'max:30'],
            'card_holder' => ['required', 'string', 'max:100'],
            'telegram_bot_token' => ['nullable', 'string', 'max:150'],
            'telegram_bot_username' => ['nullable', 'string', 'max:80'],
            'telegram_chat_id' => ['nullable', 'string', 'max:50'],
            'require_telegram_subscription' => ['nullable', 'boolean'],
            'blocked_words' => ['nullable', 'string', 'max:1000'],
        ]);

        $rawToken = trim((string) ($validated['telegram_bot_token'] ?? ''));
        if ($rawToken !== '' && $rawToken !== self::MASKED_SECRET) {
            Setting::setSecret('telegram_bot_token', $rawToken);
        }

        $botUsername = trim((string) ($validated['telegram_bot_username'] ?? ''));
        if ($botUsername === '' && ($rawToken !== '' && $rawToken !== self::MASKED_SECRET)) {
            $botUsername = (string) ($telegram->fetchBotUsername($rawToken) ?? '');
        } elseif ($botUsername !== '' && ! str_starts_with($botUsername, '@')) {
            $botUsername = '@'.$botUsername;
        }

        $channelUsername = trim((string) ($validated['telegram_username'] ?? ''));
        if ($channelUsername !== '' && ! str_starts_with($channelUsername, '@')) {
            $channelUsername = '@'.$channelUsername;
        }

        Setting::setMany([
            'site_name' => $validated['site_name'],
            'site_description' => $validated['site_description'] ?? null,
            'contact_phone' => $validated['contact_phone'],
            'telegram_username' => $channelUsername ?: null,
            'contact_address' => $validated['contact_address'] ?? null,
            'card_number' => $validated['card_number'],
            'card_holder' => $validated['card_holder'],
            'telegram_bot_username' => $botUsername ?: null,
            'telegram_chat_id' => $validated['telegram_chat_id'] ?? null,
            'require_telegram_subscription' => $request->boolean('require_telegram_subscription') ? '1' : '0',
            'blocked_words' => $validated['blocked_words'] ?? null,
        ]);

        return back()->with('success', 'Sayt sozlamalari muvaffaqiyatli saqlandi!');
    }

    public function testTelegram(TelegramNotificationService $telegram): RedirectResponse
    {
        $sent = $telegram->send(
            "🔔 <b>Sinov bildirishnomasi!</b>\n\n"
            .'✅ <b>'.e((string) Setting::get('site_name', 'RepetitorLMS'))."</b> platformasidan Telegram bot muvaffaqiyatli ulandi.\n"
            .'🕒 Vaqt: '.now()->format('d.m.Y H:i')
        );

        if (! $sent) {
            return back()->with('error', 'Telegramga xabar yuborib bo\'lmadi. Bot Token va Chat ID to\'g\'riligini hamda botga /start bosilganini tekshiring.');
        }

        return back()->with('success', 'Sinov xabari Telegram botingizga muvaffaqiyatli yuborildi!');
    }
}
