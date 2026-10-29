<?php

namespace Database\Seeders;

use App\Models\Course;
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
                    'phone' => '+998 90 911 22 33',
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

        if (Course::count() === 0) {
            $sardor = Teacher::where('slug', 'sardor-karimov')->first();
            $nilufar = Teacher::where('slug', 'nilufar-rustamova')->first();
            $ulugbek = Teacher::where('slug', 'ulugbek-rahimov')->first();

            $courses = [
                [
                    'teacher_id' => $sardor?->id,
                    'title' => 'Matematika: Milliy Sertifikat A+ va DTM Maksimal Kurs',
                    'slug' => 'matematika-milliy-sertifikat-a-plus',
                    'description' => 'Noldan boshlab murakkab tekstli masalalar, funksiyalar, hosila, integral va stereometriyani eng tez yechish usullari bilan mukammal o\'rganing.',
                    'price' => 250000,
                    'is_active' => true,
                    'lessons' => [
                        [
                            'title' => '1-dars. Natural va butun sonlar. Tezkor hisoblash sirlari (Bepul)',
                            'description' => 'Imtihonda vaqtni 3 barobar tejaydigan asosiy matematik qoidalar va mantiqiy usullar.',
                            'youtube_url' => 'https://www.youtube.com/watch?v=aircAruvnKk',
                            'position' => 1,
                            'is_free' => true,
                        ],
                        [
                            'title' => '2-dars. Kvadrat tenglamalar va Viyet teoremasining maxfiy qo\'llanilishi',
                            'description' => 'Murakkab tenglamalarni diskriminantsiz 10 soniyada yechish metodikasi.',
                            'youtube_url' => 'https://www.youtube.com/watch?v=WUvTyaaNkzM',
                            'position' => 2,
                            'is_free' => false,
                        ],
                    ],
                ],
                [
                    'teacher_id' => $nilufar?->id,
                    'title' => 'IELTS 7.5+ Intensive: Reading, Listening, Writing & Speaking',
                    'slug' => 'ielts-7-5-intensive-kurs',
                    'description' => 'Xalqaro CELTA metodikasi asosida IELTS va CEFR C1 darajasiga qisqa muddatda tayyorlovchi to\'liq video kurs.',
                    'price' => 300000,
                    'is_active' => true,
                    'lessons' => [
                        [
                            'title' => '1-dars. IELTS Reading: True / False / Not Given strategiyasi (Bepul)',
                            'description' => 'Matnni to\'liq o\'qimasdan kalit so\'zlar orqali 100% aniq javob topish.',
                            'youtube_url' => 'https://www.youtube.com/watch?v=rfscVS0vTik',
                            'position' => 1,
                            'is_free' => true,
                        ],
                    ],
                ],
                [
                    'teacher_id' => $ulugbek?->id,
                    'title' => 'Kimyo va Biologiya: Tibbiyot Oliygohlariga Tayyorlov (Bepul Boshlang\'ich)',
                    'slug' => 'kimyo-biologiya-tibbiyot-tayyorlov',
                    'description' => 'Tibbiyot yo\'nalishiga topshiruvchi abituriyentlar uchun kimyo masalalarini formulasiz, mantiqiy usulda ishlash kursi.',
                    'price' => 0,
                    'is_active' => true,
                    'lessons' => [
                        [
                            'title' => '1-dars. Kimyoviy elementlar davriy sistemasi va valentlik',
                            'description' => 'Barcha kimyoviy reaksiyalar va masalalarning poydevori.',
                            'youtube_url' => 'https://www.youtube.com/watch?v=FSyAehMdpyI',
                            'position' => 1,
                            'is_free' => true,
                        ],
                    ],
                ],
            ];

            foreach ($courses as $courseData) {
                $lessons = $courseData['lessons'];
                unset($courseData['lessons']);
                $course = Course::create($courseData);

                foreach ($lessons as $lessonData) {
                    $course->lessons()->create($lessonData);
                }
            }
        }
    }
}
