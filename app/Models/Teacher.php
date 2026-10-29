<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class Teacher extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'subject',
        'experience_years',
        'headline',
        'bio',
        'photo',
        'phone',
        'email',
        'telegram',
        'work_hours',
        'is_active',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'experience_years' => 'integer',
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo ? Storage::disk('public')->url($this->photo) : null;
    }

    public function getGradientAttribute(): string
    {
        $palette = [
            ['#6338e8', '#8f80fb'], ['#ff7a3d', '#ffb36b'], ['#0ea5e9', '#6366f1'],
            ['#10b981', '#34d399'], ['#ec4899', '#f472b6'], ['#f59e0b', '#fbbf24'],
        ];
        [$from, $to] = $palette[($this->id ?? 0) % count($palette)];

        return "linear-gradient(135deg,{$from},{$to})";
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function results(): HasMany
    {
        return $this->hasMany(TeacherResult::class)->orderBy('position')->orderByDesc('id');
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function account(): HasOne
    {
        return $this->hasOne(User::class, 'teacher_id');
    }
}
