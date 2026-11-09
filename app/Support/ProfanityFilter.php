<?php

namespace App\Support;

use App\Models\Setting;

class ProfanityFilter
{
    /**
     * O'zbek, rus va ingliz tillaridagi haqoratli va taqiqlangan so'zlar o'zagi.
     *
     * @var list<string>
     */
    private const BLOCKED_PATTERNS = [
        'jalab',
        'jallab',
        'qotaq',
        'qotoq',
        'qo\'toq',
        'qutoq',
        'sikay',
        'sikaman',
        'sikish',
        'siktir',
        'ske',
        'skay',
        'amini',
        'aminga',
        'onangni',
        'oneni',
        '오nangni',
        'ayeni',
        'haromi',
        'xaromi',
        'ko\'t',
        'kotinga',
        'ko\'tinga',
        'gandon',
        'pidaraz',
        'pidr',
        'pedik',
        'dalbayob',
        'dolbayob',
        'dalban',
        'blya',
        'blat',
        'blyat',
        'suka',
        'nahuy',
        'naxuy',
        'xuy',
        'huy',
        'pizdes',
        'pizda',
        'yeban',
        'yebat',
        'ebat',
        'fuck',
        'bitch',
        'asshole',
        'dick',
        'pussy',
        'bastard',
        'sharmanda',
        'fohisha',
        'foxisha',
    ];

    /**
     * Xabar matnini tekshiradi: so'kinish yoki ruxsatsiz reklama ssilkasi bo'lsa xato xabarini qaytaradi.
     */
    public static function validateMessage(string $text, bool $isAdmin = false): ?string
    {
        if (self::containsProfanity($text)) {
            return '⚠️ Xabarda taqiqlangan yoki nojo\'ya so\'zlar aniqlandi! Iltimos, chatda odob-axloq qoidalariga rioya qiling.';
        }

        if (! $isAdmin && self::containsSpamLink($text)) {
            return '⚠️ Chatda reklama va tashqi havolalar (ssilkalar) yuborish taqiqlangan!';
        }

        return null;
    }

    public static function containsProfanity(string $text): bool
    {
        $normalized = self::normalize($text);
        $compact = preg_replace('/[^a-z0-9]/', '', $normalized) ?? '';

        $customWords = array_filter(array_map(
            'trim',
            explode(',', mb_strtolower((string) Setting::get('blocked_words', '')))
        ));

        $allPatterns = array_merge(self::BLOCKED_PATTERNS, $customWords);

        foreach ($allPatterns as $word) {
            $cleanWord = self::normalize($word);
            $compactWord = preg_replace('/[^a-z0-9]/', '', $cleanWord) ?? '';

            if ($compactWord === '') {
                continue;
            }

            if (mb_strlen($compactWord) <= 3) {
                if (preg_match('/\b'.preg_quote($compactWord, '/').'\b/u', $normalized)) {
                    return true;
                }
            } else {
                if (str_contains($normalized, $cleanWord) || str_contains($compact, $compactWord)) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function containsSpamLink(string $text): bool
    {
        $pattern = '~(https?://|www\.|t\.me/|telegram\.me/|@[a-zA-Z0-9_]{4,}|\b[a-z0-9-]+\.(uz|ru|com|net|org|io|me)\b)~i';

        return (bool) preg_match($pattern, $text);
    }

    private static function normalize(string $text): string
    {
        $text = mb_strtolower($text);

        // Kirill harflarini va aldamchi belgilarni lotinga o'girish
        $map = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
            'е' => 'e', 'ё' => 'yo', 'ж' => 'j', 'з' => 'z', 'и' => 'i',
            'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
            'у' => 'u', 'ф' => 'f', 'х' => 'x', 'ц' => 'ts', 'ч' => 'ch',
            'ш' => 'sh', 'щ' => 'sh', 'ъ' => '', 'ы' => 'i', 'ь' => '',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya', 'қ' => 'q', 'ғ' => 'g',
            'ҳ' => 'h', 'ў' => 'o',
            '@' => 'a', '0' => 'o', '$' => 's', '1' => 'i', '!' => 'i',
        ];

        $text = strtr($text, $map);

        // Ketma-ket takrorlangan harflarni qisqartirish (masalan: jaaalaaab -> jalab)
        return preg_replace('/([a-z])\1+/u', '$1', $text) ?? $text;
    }
}
