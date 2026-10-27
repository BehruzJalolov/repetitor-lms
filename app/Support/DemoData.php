<?php

namespace App\Support;

/**
 * Frontend uchun vaqtinchalik demo ma'lumotlar.
 * Keyingi bosqichda haqiqiy Eloquent model'lar bilan almashtiriladi.
 */
class DemoData
{
    public static function courses(): array
    {
        return [
            [
                'slug' => 'matematika-dtm',
                'title' => 'Matematika: DTMga to\'liq tayyorgarlik',
                'category' => 'Matematika',
                'description' => 'Algebra, geometriya va mantiqiy masalalar — noldan DTM darajasigacha. Har bir mavzu qadam-baqadam tushuntiriladi, yakunda test mashqlari beriladi.',
                'price' => 350000,
                'icon' => '∑',
                'gradient' => 'linear-gradient(135deg,#6338e8,#8f80fb)',
                'students' => 128,
                'duration' => '24 soat',
            ],
            [
                'slug' => 'ingliz-tili-ielts',
                'title' => 'Ingliz tili: IELTS 6.5+ kursi',
                'category' => 'Ingliz tili',
                'description' => 'Listening, Reading, Writing va Speaking bo\'limlari bo\'yicha amaliy strategiyalar va real imtihon namunalari.',
                'price' => 450000,
                'icon' => 'Aa',
                'gradient' => 'linear-gradient(135deg,#ff7a3d,#ffb36b)',
                'students' => 96,
                'duration' => '32 soat',
            ],
            [
                'slug' => 'fizika-asoslari',
                'title' => 'Fizika: mexanikadan optikagacha',
                'category' => 'Fizika',
                'description' => 'Murakkab formulalarni oson tushunish usullari, masala yechish texnikasi va laboratoriya misollari.',
                'price' => 300000,
                'icon' => '⚛',
                'gradient' => 'linear-gradient(135deg,#0ea5e9,#6366f1)',
                'students' => 74,
                'duration' => '20 soat',
            ],
            [
                'slug' => 'kimyo-dtm',
                'title' => 'Kimyo: organikdan noorganikkacha',
                'category' => 'Kimyo',
                'description' => 'Reaksiyalar, tenglamalar va masalalar — barchasi sodda tilda, ko\'plab test mashqlari bilan.',
                'price' => 320000,
                'icon' => '⚗',
                'gradient' => 'linear-gradient(135deg,#10b981,#34d399)',
                'students' => 61,
                'duration' => '22 soat',
            ],
            [
                'slug' => 'ona-tili-adabiyot',
                'title' => 'Ona tili va adabiyot',
                'category' => 'Ona tili',
                'description' => 'Grammatika, imlo qoidalari va adabiyot bo\'yicha imtihonga tayyorlovchi to\'liq kurs.',
                'price' => 250000,
                'icon' => 'Ўз',
                'gradient' => 'linear-gradient(135deg,#ec4899,#f472b6)',
                'students' => 52,
                'duration' => '18 soat',
            ],
            [
                'slug' => 'tarix-jahon-ozbekiston',
                'title' => 'Tarix: O\'zbekiston va jahon',
                'category' => 'Tarix',
                'description' => 'Muhim sanalar, voqealar va shaxslar — yodlashning oson usullari bilan.',
                'price' => 250000,
                'icon' => '⌛',
                'gradient' => 'linear-gradient(135deg,#f59e0b,#fbbf24)',
                'students' => 43,
                'duration' => '16 soat',
            ],
        ];
    }

    public static function course(string $slug): ?array
    {
        foreach (static::courses() as $course) {
            if ($course['slug'] === $slug) {
                $course['lessons'] = static::lessons();

                return $course;
            }
        }

        return null;
    }

    public static function lessons(): array
    {
        $titles = [
            'Kirish: kurs rejasi va o\'qish usuli',
            'Asosiy tushunchalar va ta\'riflar',
            'Birinchi mavzu: nazariya va misollar',
            'Amaliy mashg\'ulot: masalalar yechish',
            'Murakkab masalalar tahlili',
            'Oraliq test va xatolar ustida ishlash',
            'Ikkinchi mavzu: chuqurlashtirilgan darslik',
            'Yakuniy takrorlash va imtihon strategiyasi',
        ];

        $lessons = [];
        foreach ($titles as $i => $title) {
            $lessons[] = [
                'id' => $i + 1,
                'title' => $title,
                'duration' => (12 + ($i * 3) % 17).' daqiqa',
                'is_free' => $i === 0,
                'has_file' => $i % 2 === 1,
                'is_completed' => $i === 0,
                'youtube_id' => 'aqz-KE-bpKQ',
                'description' => 'Ushbu darsda mavzuning asosiy g\'oyalari sodda tilda tushuntiriladi. Videoni ko\'rib bo\'lgach, biriktirilgan konspektni yuklab oling va mashqlarni bajaring.',
            ];
        }

        return $lessons;
    }
}
