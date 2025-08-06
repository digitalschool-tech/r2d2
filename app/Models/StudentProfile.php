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

    public function updateFromQuiz(): void
    {
        $averages = $this->quizzes()
            ->whereNotNull('ttc')
            ->whereNotNull('completion_pct')
            ->selectRaw('AVG(ttc) as avg_ttc, AVG(completion_pct) as avg_completion_pct')
            ->first();

        $this->update([
            'ttc' => $averages->avg_ttc !== null ? round($averages->avg_ttc) : null,
            'completion_pct' => $averages->avg_completion_pct !== null ? round($averages->avg_completion_pct) : null,
        ]);
    }
    public function computeSkillProfile(): ?array
    {
        $quizzes = $this->quizzes()
            ->whereNotNull('performance')
            ->get();

        if ($quizzes->isEmpty()) {
            return null;
        }

        return [
            'speed' => max(0, min(100, 120 - $quizzes->avg('ttc'))), // Normalize inverse
            'accuracy' => round($quizzes->avg('completion_pct'), 2),
            'performance' => round($quizzes->avg('performance'), 2),
            'resilience' => round(100 - (
                $quizzes->pluck('wrong_questions')->flatten()->count()
                / max(1, $quizzes->count())
            ) * 10, 2),
        ];
    }
}

