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

    private static int $subscriptionCacheVersion = 1;

    private int $memoizedVersion = 0;

    private ?bool $memoizedActiveSubscription = null;

    /**
     * @var array<int, bool>
     */
    private array $memoizedCourseAccess = [];

    public static function bumpSubscriptionCacheVersion(): void
    {
        self::$subscriptionCacheVersion++;
    }

    private function syncMemoizedCacheVersion(): void
    {
        if ($this->memoizedVersion !== self::$subscriptionCacheVersion) {
            $this->memoizedVersion = self::$subscriptionCacheVersion;
            $this->memoizedActiveSubscription = null;
            $this->memoizedCourseAccess = [];
        }
    }

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
     * Ustoz faqat o'ziga biriktirilgan kursni (Bosh Admin esa barcha kurslarni) boshqara oladi.
     */
    public function canManageCourse(Course $course): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (! $this->isTeacher() || ! $this->hasPermission('manage_courses')) {
            return false;
        }

        return $this->teacher_id !== null && $course->teacher_id === $this->teacher_id;
    }

    /**
     * Ustoz faqat o'ziga tegishli jonli efirni (Bosh Admin esa barcha efirlarni) boshqara oladi.
     */
    public function canManageStream(LiveStream $stream): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (! $this->isTeacher() || ! $this->hasPermission('manage_streams')) {
            return false;
        }

        return $this->teacher_id !== null && $stream->teacher_id === $this->teacher_id;
    }

    /**
     * Ustoz faqat o'zining kurslariga yuborilgan to'lov cheklarini ko'ra oladi va tasdiqlay oladi.
     */
    public function canManageSubscription(Subscription $subscription): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (! $this->isTeacher() || ! $this->hasPermission('manage_subscriptions')) {
            return false;
        }

        $course = $subscription->relationLoaded('course')
            ? $subscription->course
            : $subscription->course()->first();

        return $this->teacher_id !== null && $course && $course->teacher_id === $this->teacher_id;
    }

    /**
     * Ustoz faqat o'zining kurslariga (yoki umumiy) yuborilgan murojaatlarni boshqara oladi.
     */
    public function canManageLead(Lead $lead): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (! $this->isTeacher() || ! $this->hasPermission('manage_leads')) {
            return false;
        }

        if ($this->teacher_id === null) {
            return false;
        }

        if ($lead->course_id === null) {
            return true;
        }

        $course = $lead->relationLoaded('course')
            ? $lead->course
            : $lead->course()->first();

        return $course && $course->teacher_id === $this->teacher_id;
    }

    /**
     * Foydalanuvchi kursning pullik darslarini ko'ra oladimi?
     * Bosh Admin, kursning o'z ustozi yoki bepul kurs — doim; o'quvchi — faqat tasdiqlangan va muddati o'tmagan obuna bilan.
     */
    public function hasAccessTo(Course $course): bool
    {
        if ($this->isSuperAdmin() || $course->price === 0) {
            return true;
        }

        if ($this->isTeacher() && $this->teacher_id !== null && $course->teacher_id === $this->teacher_id) {
            return true;
        }

        $this->syncMemoizedCacheVersion();

        if ($course->id !== null && array_key_exists($course->id, $this->memoizedCourseAccess)) {
            return $this->memoizedCourseAccess[$course->id];
        }

        $hasAccess = $this->subscriptions()
            ->where('course_id', $course->id)
            ->where('status', 'approved')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->exists();

        if ($course->id !== null) {
            $this->memoizedCourseAccess[$course->id] = $hasAccess;
        }

        return $hasAccess;
    }

    /**
     * Foydalanuvchi kamida bitta tasdiqlangan (faol) kurs obunasiga egami yoki Admin/Ustozmi?
     * Jonli efir va Chat bo'limlari faqat kurs sotib olgan o'quvchilar hamda ustoz/adminlar uchun ochiq.
     */
    public function hasActiveSubscription(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $this->syncMemoizedCacheVersion();

        if ($this->memoizedActiveSubscription !== null) {
            return $this->memoizedActiveSubscription;
        }

        return $this->memoizedActiveSubscription = $this->subscriptions()
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
