<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * @return array<string, ?string>
     */
    public static function allCached(): array
    {
        try {
            return Cache::rememberForever('site_settings', function (): array {
                if (! Schema::hasTable('settings')) {
                    return [];
                }

                return static::query()->pluck('value', 'key')->all();
            });
        } catch (\Throwable) {
            return [];
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = static::allCached();

        if (array_key_exists($key, $all) && $all[$key] !== null && $all[$key] !== '') {
            return $all[$key];
        }

        return $default;
    }

    public static function getSecret(string $key, mixed $default = null): mixed
    {
        $raw = static::get($key);

        if ($raw === null || $raw === '') {
            return $default;
        }

        $rawStr = (string) $raw;

        if (str_starts_with($rawStr, 'b64:')) {
            $decoded = base64_decode(substr($rawStr, 4), true);

            return ($decoded !== false && $decoded !== '') ? $decoded : $default;
        }

        try {
            return Crypt::decryptString($rawStr);
        } catch (DecryptException) {
            // Agar eski APP_KEY bilan shifrlangan bo'lsa (eyJpdiI6...), zaxira nusxasini tekshiramiz
            $backup = static::get($key.'_b64');
            if (is_string($backup) && str_starts_with($backup, 'b64:')) {
                $decoded = base64_decode(substr($backup, 4), true);
                if ($decoded !== false && $decoded !== '') {
                    return $decoded;
                }
            }

            if (str_starts_with($rawStr, 'eyJpdiI6')) {
                return $default;
            }

            return $rawStr;
        }
    }

    public static function setSecret(string $key, ?string $plainValue): void
    {
        $clean = ($plainValue !== null && trim($plainValue) !== '') ? trim($plainValue) : null;
        $encoded = $clean !== null ? 'b64:'.base64_encode($clean) : null;

        static::setMany([
            $key => $encoded,
            $key.'_b64' => $encoded,
        ]);
    }

    /**
     * @param  array<string, ?string>  $values
     */
    public static function setMany(array $values): void
    {
        $now = now();
        $rows = [];

        foreach ($values as $key => $value) {
            $rows[] = [
                'key' => $key,
                'value' => $value,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows !== []) {
            static::query()->upsert($rows, ['key'], ['value', 'updated_at']);
        }

        Cache::forget('site_settings');
    }
}
