<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EskizSmsService
{
    protected ?string $email;

    protected ?string $password;

    protected string $from;

    protected string $baseUrl;

    public function __construct()
    {
        $this->email = config('sms.eskiz.email');
        $this->password = config('sms.eskiz.password');
        $this->from = config('sms.eskiz.from', '4546');
        $this->baseUrl = rtrim(config('sms.eskiz.base_url', 'https://notify.eskiz.uz/api'), '/');
    }

    /**
     * SMS xabar yuborish
     */
    public function send(string $phone, string $message): bool
    {
        $cleanPhone = preg_replace('/\D+/', '', $phone);

        // Agar Eskiz ma'lumotlari kiritilmagan bo'lsa: faqat lokal va test muhitida logga yozamiz
        if (empty($this->email) || empty($this->password)) {
            if (app()->isLocal() || app()->runningUnitTests()) {
                Log::info("Eskiz SMS (Lokal/Test) -> Tel: {$cleanPhone} | Xabar: {$message}");

                return true;
            }

            Log::error('Eskiz SMS sozlanmagan (ESKIZ_EMAIL va ESKIZ_PASSWORD kiritilmagan).');

            return false;
        }

        $token = $this->getToken();
        if (! $token) {
            Log::error('Eskiz SMS: Avtorizatsiya tokeni olinmadi.');

            return false;
        }

        try {
            $response = Http::withToken($token)
                ->asForm()
                ->timeout(10)
                ->post("{$this->baseUrl}/message/sms/send", [
                    'mobile_phone' => $cleanPhone,
                    'message' => $message,
                    'from' => $this->from,
                ]);

            if ($response->successful()) {
                return true;
            }

            // Agar token eskirgan bo'lsa (401), keshni tozalab yana bir bor urinib ko'ramiz
            if ($response->status() === 401) {
                Cache::forget('eskiz_sms_token');
                $token = $this->getToken();
                if ($token) {
                    $retry = Http::withToken($token)
                        ->asForm()
                        ->timeout(10)
                        ->post("{$this->baseUrl}/message/sms/send", [
                            'mobile_phone' => $cleanPhone,
                            'message' => $message,
                            'from' => $this->from,
                        ]);

                    return $retry->successful();
                }
            }

            Log::error('Eskiz SMS xatolik: '.$response->body());

            return false;
        } catch (\Throwable $e) {
            Log::error('Eskiz SMS exception: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Eskiz API dan Bearer token olish va keshga saqlash
     */
    protected function getToken(): ?string
    {
        return Cache::remember('eskiz_sms_token', now()->addDays(20), function () {
            try {
                $response = Http::asForm()
                    ->timeout(10)
                    ->post("{$this->baseUrl}/auth/login", [
                        'email' => $this->email,
                        'password' => $this->password,
                    ]);

                if ($response->successful()) {
                    return $response->json('data.token');
                }

                Log::error('Eskiz login xatosi: '.$response->body());

                return null;
            } catch (\Throwable $e) {
                Log::error('Eskiz login exception: '.$e->getMessage());

                return null;
            }
        });
    }
}
