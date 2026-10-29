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

    public function getPhotoUrlAttribute(): string
    {
        if (is_string($this->photo) && $this->photo !== '') {
            if (
                str_starts_with($this->photo, 'http://')
                || str_starts_with($this->photo, 'https://')
                || str_starts_with($this->photo, 'data:image/')
                || str_starts_with($this->photo, '/')
            ) {
                return $this->photo;
            }

            if (Storage::disk('public')->exists($this->photo)) {
                return Storage::disk('public')->url($this->photo);
            }
        }

        $bySlug = [
            'sardor-karimov' => 'https://images.unsplash.com/photo-1568602471122-7832951cc4c5?auto=format&fit=crop&w=800&q=80',
            'nilufar-rustamova' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=800&q=80',
            'ulugbek-rahimov' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=800&q=80',
        ];

        if ($this->slug && isset($bySlug[$this->slug])) {
            return $bySlug[$this->slug];
        }

        $defaults = [
            'https://images.unsplash.com/photo-1568602471122-7832951cc4c5?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=800&q=80',
        ];

        return $defaults[($this->id ?? 0) % count($defaults)];
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
