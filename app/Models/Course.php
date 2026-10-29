<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Course extends Model
{
    protected $fillable = [
        'teacher_id',
        'title',
        'slug',
        'description',
        'thumbnail',
        'price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->thumbnail ? Storage::disk('public')->url($this->thumbnail) : null;
    }

    /**
     * Muqova rasmi bo'lmaganda ko'rsatiladigan gradient (kurs ID siga qarab doimiy).
     */
    public function getGradientAttribute(): string
    {
        $palette = [
            ['#6338e8', '#8f80fb'], ['#ff7a3d', '#ffb36b'], ['#0ea5e9', '#6366f1'],
            ['#10b981', '#34d399'], ['#ec4899', '#f472b6'], ['#f59e0b', '#fbbf24'],
        ];
        [$from, $to] = $palette[$this->id % count($palette)];

        return "linear-gradient(135deg,{$from},{$to})";
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('position');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
