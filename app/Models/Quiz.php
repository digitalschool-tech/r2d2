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
        'questions',
        'student_answers',
        'wrong_question_ids',
        'ttc',
        'completion_pct',
    ];

    protected $casts = [
        'questions' => 'array',
        'student_answers' => 'array',
        'wrong_question_ids' => 'array',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class, 'external_student_id', 'external_student_id');
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }
}
