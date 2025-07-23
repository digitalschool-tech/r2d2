<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentProfile extends Model
{
    use HasFactory;

    protected $table = 'student_profiles'; 

    protected $fillable = [
        'external_student_id',
        'ttc',
        'completion_pct',
    ];

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class, 'external_student_id', 'external_student_id');
    }
    
    public function updateFromQuiz(int $ttc, int $completionPct): void
    {
        $this->update([
            'ttc' => $ttc,
            'completion_pct' => $completionPct,
        ]);
    }
}
