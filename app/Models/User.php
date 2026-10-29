<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'phone', 'email', 'telegram_chat_id', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Ustozlarga Bosh Admin tomonidan berilishi mumkin bo'lgan barcha huquqlar.
     *
     * @var array<string, string>
     */
    public const TEACHER_PERMISSIONS = [
        'manage_courses' => '📚 Kurslar, video darslar va testlarni boshqarish',
        'manage_streams' => '🔴 Jonli efir (Stream) yaratish va o\'tkazish',
        'manage_results' => '🏆 O\'z profili va o\'quvchilari sertifikat/natijalarini boshqarish',
        'moderate_chat' => '💬 Chatda ustoz imtiyozi (Xabar qadash, o\'chirish va qoidabuzarni bloklash)',
        'manage_leads' => '📞 Konsultatsiya murojaatlari (Lidlar) bilan ishlash',
        'manage_subscriptions' => '🧾 To\'lov cheklarini ko\'rish va tasdiqlash',
        'view_students' => '👥 O\'quvchilar ro\'yxatini ko\'rish',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => 'student',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_chat_banned' => 'boolean',
            'permissions' => 'array',
        ];
    }

    /**
     * Bosh Admin (Markaz rahbari) ekanligini tekshiradi.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Ustoz hisobi ekanligini tekshiradi.
     */
    public function isTeacher(): bool
    {
        return $this->role === 'teacher';
    }

    /**
     * Boshqaruv paneliga (Admin/Ustoz paneliga) kira oladimi?
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'teacher'], true);
    }

    /**
     * Foydalanuvchida ko'rsatilgan ruxsat (`permission`) borligini tekshiradi.
     * Bosh Adminda har doim barcha ruxsatlar bor.
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (! $this->isTeacher()) {
            return false;
        }

        $granted = is_array($this->permissions) ? $this->permissions : [];

        return in_array($permission, $granted, true);
    }

    public function teacherProfile(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    /**
     * Foydalanuvchi kursning pullik darslarini ko'ra oladimi?
     * Admin, Ustoz yoki bepul kurs — doim; o'quvchi — faqat tasdiqlangan va muddati o'tmagan obuna bilan.
     */
    public function hasAccessTo(Course $course): bool
    {
        if ($this->isAdmin() || $course->price === 0) {
            return true;
        }

        return $this->subscriptions()
            ->where('course_id', $course->id)
            ->where('status', 'approved')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->exists();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function lessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class)
            ->withPivot(['is_completed', 'quiz_score', 'quiz_passed'])
            ->withTimestamps();
    }
}
