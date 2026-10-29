<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

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
        return $this->student_photo ? Storage::disk('public')->url($this->student_photo) : null;
    }

    public function getCertificateUrlAttribute(): ?string
    {
        return $this->certificate_image ? Storage::disk('public')->url($this->certificate_image) : null;
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}
