<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

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
        if (! is_string($this->image_path) || $this->image_path === '') {
            return null;
        }

        if (
            str_starts_with($this->image_path, 'http://')
            || str_starts_with($this->image_path, 'https://')
            || str_starts_with($this->image_path, 'data:image/')
            || str_starts_with($this->image_path, '/')
        ) {
            return $this->image_path;
        }

        return Storage::disk('public')->url($this->image_path);
    }
}
