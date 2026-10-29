<?php

namespace App\Console\Commands;

use App\Services\TelegramNotificationService;
use Illuminate\Console\Command;

class TelegramPollCommand extends Command
{
    protected $signature = 'telegram:work {--once : Faqat bir marta tekshirib chiqish}';

    protected $description = 'Telegram bot xabarlari (/start) va inline tugmalarni (Obuna bo\'ldim, Kod olish) jonli qayta ishlaydi';

    public function handle(TelegramNotificationService $telegram): int
    {
        if ($this->option('once')) {
            $telegram->processBotUpdates();

            return self::SUCCESS;
        }

        $this->info('Telegram bot tinglanmoqda... (To\'xtatish uchun Ctrl+C)');

        while (true) {
            $telegram->processBotUpdates();
            sleep(2);
        }
    }
}
