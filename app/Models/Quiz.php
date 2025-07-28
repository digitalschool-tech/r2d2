<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_student_id',
        'curriculum_id',
        'quiz_data',
        'wrong_questions',
        'ttc',
        'completion_pct',
        'performance',
        'difficulty_level',
    ];

    protected $casts = [
        'quiz_data' => 'array',
        'wrong_questions' => 'array',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class, 'external_student_id', 'external_student_id');
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }

     protected static function booted(): void
    {
        static::created(function (Quiz $quiz) {
            if ($quiz->student) {
                $quiz->student->updateFromQuiz();
            }
        });
    }
}
