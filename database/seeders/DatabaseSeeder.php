<?php

namespace Database\Seeders;

use App\Models\Teacher;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Admin (repetitor) hisobi. Ma'lumotlar config/admin.php (.env) dan olinadi.
        $phone = Phone::normalize(config('admin.phone')) ?? config('admin.phone');

        $user = User::firstOrNew(['phone' => $phone]);
        $user->forceFill([
            'name' => config('admin.name'),
            'email' => config('admin.email'),
            'role' => 'admin',
            'password' => config('admin.password'),
        ])->save();

        if (Teacher::count() === 0) {
            $teachers = [
                [
                    'name' => 'Sardor Karimov',
                    'slug' => 'sardor-karimov',
                    'subject' => 'Matematika va Mantiq',
                    'experience_years' => 9,
                    'headline' => 'Milliy sertifikat A+ (100%) sohibi · 450+ o\'quvchisi davlat granti asosida talaba bo\'lgan',
                    'bio' => "9 yillik tajribaga ega oliy toifali matematika repetitori. Murakkab mavzularni eng sodda va tez yechiladigan usullar (layfxaklar) bilan tushuntiradi.\n\nHar bir o'quvchi bilan individual test tahlili va DTM / Milliy sertifikat standartidagi baza ustida ishlanadi.",
                    'phone' => '+998 90 123 45 67',
                    'email' => 'sardor.math@repetitor.uz',
                    'telegram' => '@sardor_math_uz',
                    'work_hours' => 'Dushanba–Shanba: 09:00 – 20:00',
                    'position' => 1,
                    'results' => [
                        [
                            'student_name' => 'Jasmina Alimova',
                            'score' => '189.0 ball (Maksimal)',
                            'achievement' => 'Toshkent Axborot Texnologiyalari Universiteti — Davlat granti',
                            'testimonial' => 'Sardor ustozning video darslari va har darsdan keyingi PDF testlari sababli matematikadan 30 tadan 30 ta yechdim va Milliy sertifikatdan A+ oldim!',
                        ],
                        [
                            'student_name' => 'Javohir Tursunov',
                            'score' => 'Milliy sertifikat A+ (98%)',
                            'achievement' => 'Toshkent Davlat Iqtisodiyot Universiteti — Grant',
                            'testimonial' => 'Noldan boshlab 5 oy ichida Milliy sertifikat imtihonidan A+ darajani qo\'lga kiritdim.',
                        ],
                    ],
                ],
                [
                    'name' => 'Nilufar Rustamova',
                    'slug' => 'nilufar-rustamova',
                    'subject' => 'Ingliz tili (IELTS & CEFR)',
                    'experience_years' => 7,
                    'headline' => 'IELTS 8.5 (C2) va CELTA sertifikati sohibasi · 300+ o\'quvchisi IELTS 7.0+ olgan',
                    'bio' => "Xalqaro CELTA sertifikatiga ega ingliz tili ustozi. IELTS Reading, Listening, Writing va Speaking bo'limlaridan yuqori ball olish strategiyalarini mukammal o'rgatadi.",
                    'phone' => '+998 93 555 44 33',
                    'email' => 'nilufar.ielts@repetitor.uz',
                    'telegram' => '@nilufar_ielts',
                    'work_hours' => 'Dushanba–Shanba: 10:00 – 19:00',
                    'position' => 2,
                    'results' => [
                        [
                            'student_name' => 'Azizbek Mahmudov',
                            'score' => 'IELTS 8.0 (Listening 9.0)',
                            'achievement' => 'Westminster International University — 100% Scholarship',
                            'testimonial' => 'Writing va Speaking darslaridagi aniq shablonlar va tahlillar orqali 6.0 dan 8.0 ga chiqdim!',
                        ],
                        [
                            'student_name' => 'Madina Qodirova',
                            'score' => 'CEFR C1 (Milliy sertifikat)',
                            'achievement' => 'O\'zDJTU — Davlat granti (Ingliz tilidan maksimal ball)',
                            'testimonial' => 'Ustozning har bir darsdagi lug\'at va konspektlari imtihonda juda katta yordam berdi.',
                        ],
                    ],
                ],
                [
                    'name' => 'Ulug\'bek Rahimov',
                    'slug' => 'ulugbek-rahimov',
                    'subject' => 'Kimyo va Biologiya',
                    'experience_years' => 11,
                    'headline' => 'Tibbiyot oliygohlariga tayyorlash bo\'yicha 11 yillik ekspert · 600+ bo\'lajak shifokorlar ustozi',
                    'bio' => "Tibbiyot yo'nalishiga topshiruvchi abituriyentlar uchun maxsus intensiv dastur muallifi. Kimyo masalalarini formulasiz, mantiqiy usulda 30 soniyada ishlash metodikasi asoschisi.",
                    'phone' => '+998 97 777 88 99',
                    'email' => 'ulugbek.chem@repetitor.uz',
                    'telegram' => '@ulugbek_kimyo',
                    'work_hours' => 'Dushanba–Juma: 08:30 – 18:30',
                    'position' => 3,
                    'results' => [
                        [
                            'student_name' => 'Abrorbek Yo\'ldoshev',
                            'score' => '186.4 ball (Grant)',
                            'achievement' => 'Toshkent Tibbiyot Akademiyasi (TMA) — Davlat granti',
                            'testimonial' => 'Kimyodan masalalarni yechishga juda qiynalar edim, Ulug\'bek ustozning metodikasi bilan 100% natija qayd etdim.',
                        ],
                    ],
                ],
            ];

            foreach ($teachers as $item) {
                $results = $item['results'];
                unset($item['results']);
                $teacher = Teacher::create($item);

                foreach ($results as $idx => $res) {
                    $res['position'] = $idx + 1;
                    $teacher->results()->create($res);
                }
            }
        }
    }
}
