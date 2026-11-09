<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class LessonUser extends Pivot
{
    protected $table = 'lesson_user';

    public $incrementing = true;

    protected $fillable = [
        'user_id',
        'lesson_id',
        'is_completed',
        'quiz_score',
        'quiz_passed',
    ];

    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
            'quiz_score' => 'integer',
            'quiz_passed' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
