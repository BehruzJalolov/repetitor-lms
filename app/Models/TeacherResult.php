<?php

namespace App\Models;

use App\Support\MediaStorage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherResult extends Model
{
    protected $fillable = [
        'teacher_id',
        'student_name',
        'score',
        'achievement',
        'testimonial',
        'student_photo',
        'certificate_image',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    public function getStudentPhotoUrlAttribute(): ?string
    {
        return MediaStorage::url($this->student_photo);
    }

    public function getCertificateUrlAttribute(): ?string
    {
        return MediaStorage::url($this->certificate_image);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}
