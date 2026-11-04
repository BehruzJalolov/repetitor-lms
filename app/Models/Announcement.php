<?php

namespace App\Models;

use App\Support\MediaStorage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    public const CATEGORIES = [
        'yangilik' => '📰 Yangilik',
        'elon' => '📢 Muhim e\'lon',
        'chegirma' => '🔥 Aksiya va chegirma',
        'natija' => '🏆 O\'quvchilar natijasi',
    ];

    protected $fillable = [
        'user_id',
        'teacher_id',
        'title',
        'slug',
        'category',
        'excerpt',
        'body',
        'image_path',
        'link_url',
        'link_label',
        'is_pinned',
        'is_active',
        'views_count',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'is_active' => 'boolean',
            'views_count' => 'integer',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? '📰 Yangilik';
    }

    public function getImageUrlAttribute(): ?string
    {
        return MediaStorage::url($this->image_path);
    }
}
