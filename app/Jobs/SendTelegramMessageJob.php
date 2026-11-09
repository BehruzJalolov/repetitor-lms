<?php

namespace App\Jobs;

use App\Services\TelegramNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendTelegramMessageJob implements ShouldQueue
{
    use Queueable;

    /**
     * Qayta urinishlar soni (tarmoq uzilishlarida).
     */
    public int $tries = 3;

    /**
     * Har bir urinish orasidagi kutish vaqti (soniya).
     *
     * @var list<int>
     */
    public array $backoff = [2, 5, 15];

    /**
     * Maksimal bajarilish vaqti (soniya).
     */
    public int $timeout = 15;

    /**
     * @param  array<int, array<int, array<string, string>>>|null  $inlineKeyboard
     */
    public function __construct(
        public string $message,
        public string|int|null $chatId = null,
        public ?array $inlineKeyboard = null,
    ) {}

    public function handle(TelegramNotificationService $telegram): void
    {
        if ($this->chatId !== null) {
            $telegram->sendToChat($this->chatId, $this->message, $this->inlineKeyboard);

            return;
        }

        $telegram->send($this->message);
    }
}
