# PROJECT_SPEC — Repetitor LMS

Mahalliy o'quv markazi (repetitor) uchun online kurslar va video darslar platformasi.
Keyingi barcha qadamlarda shu qoidalarga amal qilinadi.

## 1. Texnologiyalar
- Backend: PHP 8.2+, Laravel 11.
- Frontend: Laravel Blade, Tailwind CSS, Alpine.js (zamonaviy, chiroyli, responsive UI).
- Baza: MySQL.
- Til: Saytning barcha interfeysi O'zbek tilida (lotin alifbosida).

## 2. Foydalanuvchi rollari
- `admin` (Repetitor): kurslar qo'shadi, darslarni (YouTube Unlisted link) yuklaydi, o'quvchilar yuborgan to'lov cheklarini (skrinshot) ko'rib tasdiqlaydi (`approved`) yoki rad etadi (`rejected`).
- `student` (O'quvchi): telefon raqami va parol orqali ro'yxatdan o'tadi, kurslarni ko'radi, 1-bepul darsni ko'radi, qolgan darslarni ochish uchun karta raqamga to'lov qilib chek rasmini yuklaydi.

## 3. Ma'lumotlar bazasi (Database Schema)
- `users`: id, name, phone (unique, login uchun), role ('admin', 'student'), password.
- `courses`: id, title, slug, description, thumbnail, price (so'mda), is_active.
- `lessons`: id, course_id, title, description, youtube_url, file_path (konspekt/test PDF), position (tartib raqami), is_free (boolean — namuna dars uchun).
- `subscriptions`: id, user_id, course_id, receipt_image (chek rasmi), status ('pending', 'approved', 'rejected'), admin_comment, expires_at.
- `lesson_user`: id, user_id, lesson_id, is_completed (darsni ko'rib bo'lganini belgilash).

### Bog'lanishlar
- Course hasMany Lesson, Subscription
- Lesson belongsTo Course; belongsToMany User (`lesson_user`)
- Subscription belongsTo User, Course
- User hasMany Subscription; belongsToMany Lesson (`lesson_user`)

## 4. Dizayn talabi
- Tailwind CSS orqali zamonaviy EdTech platformalaridek (Udemy/Coursera uslubida, lekin sodda va tushunarli) dizayn.

## 5. Bosqichlar
1. **1-Bosqich:** Laravel, PROJECT_SPEC.md, migration va modellar. ✅
2. Keyingi bosqichlar: auth, controller va view'lar (hali boshlanmagan).
