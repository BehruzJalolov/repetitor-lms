<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    protected $fillable = [
        'course_id',
        'title',
        'description',
        'youtube_url',
        'video_path',
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
     * Har qanday YouTube havolasidan (watch, youtu.be, embed, shorts, live, mobile, iframe) yoki 11 belgili ID dan video ID sini ajratadi.
     */
    public static function extractYoutubeId(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $url = trim($url);

        if (preg_match('/^[A-Za-z0-9_-]{11}$/', $url)) {
            return $url;
        }

        $pattern = '~(?:(?:www\.|m\.)?youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/|v/)|youtu\.be/)([A-Za-z0-9_-]{11})~i';

        return preg_match($pattern, $url, $m) ? $m[1] : null;
    }

    public function getYoutubeIdAttribute(): ?string
    {
        return static::extractYoutubeId($this->youtube_url);
    }

    public function hasUploadedVideo(): bool
    {
        return ! empty($this->video_path);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(LessonQuestion::class)->orderBy('position')->orderBy('id');
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class)->latest();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['is_completed', 'quiz_score', 'quiz_passed'])
            ->withTimestamps();
    }
}
