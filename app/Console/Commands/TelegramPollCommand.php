<?php

namespace App\Console\Commands;

use App\Services\TelegramNotificationService;
use Illuminate\Console\Command;

class TelegramPollCommand extends Command
{
    protected $signature = 'telegram:work
                            {--once : Faqat bir marta tekshirib chiqish}
                            {--timeout=0 : Necha soniya ishlagach jarayonni yakunlash (0 = cheksiz)}';

    protected $description = 'Telegram bot xabarlari (/start) va inline tugmalarni (Obuna bo\'ldim, Kod olish) jonli qayta ishlaydi';

    public function handle(TelegramNotificationService $telegram): int
    {
        if ($this->option('once')) {
            $telegram->processBotUpdates();

            return self::SUCCESS;
        }

        $timeout = max(0, (int) $this->option('timeout'));
        $startedAt = time();

        $this->info('Telegram bot tinglanmoqda...');

        while (true) {
            $telegram->processBotUpdates();

            if ($timeout > 0 && (time() - $startedAt) >= $timeout) {
                break;
            }

            usleep(1500000);
        }

        return self::SUCCESS;
    }
}
