<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Lesson extends Model
{
    protected $fillable = [
        'course_id',
        'title',
        'description',
        'youtube_url',
        'file_path',
        'position',
        'is_free',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_free' => 'boolean',
        ];
    }

    /**
     * YouTube havolasidan video ID sini ajratadi (watch, youtu.be, embed, shorts).
     */
    public static function extractYoutubeId(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $pattern = '~(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})~i';

        return preg_match($pattern, $url, $m) ? $m[1] : null;
    }

    public function getYoutubeIdAttribute(): ?string
    {
        return static::extractYoutubeId($this->youtube_url);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('is_completed')
            ->withTimestamps();
    }
}
