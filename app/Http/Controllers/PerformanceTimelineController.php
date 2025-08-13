<?php

namespace App\Http\Controllers;

use App\Models\StudentProfile;
use App\Models\Quiz;
use App\Models\Curriculum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PerformanceTimelineController extends Controller
{
    public function index()
    {
        // Load all students with their quiz data
        $students = StudentProfile::with(['quizzes.curriculum'])
            ->whereHas('quizzes')
            ->get();

        // Load all curricula for lesson progression
        $curricula = Curriculum::orderBy('unit')
            ->orderBy('lesson')
            ->get();

        // Load all quizzes for stats
        $quizzes = Quiz::with(['curriculum', 'student'])
            ->whereNotNull('ttc')
            ->whereNotNull('completion_pct')
            ->get();

        // Calculate average completion
        $avgCompletion = $quizzes->avg('completion_pct');

        // Build timeline data
        $timelineData = $this->buildTimelineData($students);
        
        // Build difficulty progression data
        $difficultyProgression = $this->buildDifficultyProgression($quizzes);
        
        // Build lesson statistics
        $lessonStats = $this->buildLessonStats($quizzes);

        // Build chart data
        $chartData = $this->buildChartData($quizzes);

        return view('performance-timeline', [
            'students' => $students,
            'curricula' => $curricula,
            'quizzes' => $quizzes,
            'avgCompletion' => $avgCompletion,
            'timelineData' => $timelineData,
            'difficultyProgression' => $difficultyProgression,
            'lessonStats' => $lessonStats,
            'chartLabels' => $chartData['labels'],
            'chartCompletionData' => $chartData['completion'],
            'chartTTCData' => $chartData['ttc'],
        ]);
    }

    private function buildTimelineData($students)
    {
        $timelineData = [];

        foreach ($students as $student) {
            $studentData = [
                'student_id' => $student->external_student_id,
                'quizzes' => [],
            ];

            // Get quizzes ordered by date
            $studentQuizzes = $student->quizzes()
                ->with('curriculum')
                ->whereNotNull('ttc')
                ->whereNotNull('completion_pct')
                ->orderBy('created_at')
                ->get();

            foreach ($studentQuizzes as $quiz) {
                $studentData['quizzes'][] = [
                    'id' => $quiz->id,
                    'unit' => $quiz->curriculum->unit,
                    'lesson_number' => $quiz->curriculum->lesson,
                    'title' => $quiz->curriculum->title,
                    'date' => $quiz->created_at->format('Y-m-d'),
                    'ttc' => $quiz->ttc,
                    'completion_pct' => $quiz->completion_pct,
                    'performance' => $quiz->performance,
                    'difficulty' => $quiz->difficulty_level,
                    'wrong_questions' => is_array($quiz->wrong_questions) ? count($quiz->wrong_questions) : 0,
                ];
            }

            if (!empty($studentData['quizzes'])) {
                $timelineData[] = $studentData;
            }
        }

        return $timelineData;
    }

    private function buildDifficultyProgression($quizzes)
    {
        $difficultyProgression = [];

        // Group quizzes by lesson and analyze difficulty progression
        $lessonQuizzes = $quizzes->groupBy(function ($quiz) {
            return $quiz->curriculum->unit . '_' . $quiz->curriculum->lesson;
        });

        foreach ($lessonQuizzes as $lessonKey => $lessonQuizzes) {
            $curriculum = $lessonQuizzes->first()->curriculum;
            
            $difficultyStats = [
                'easy' => ['count' => 0, 'avg_completion' => 0, 'avg_ttc' => 0],
                'medium' => ['count' => 0, 'avg_completion' => 0, 'avg_ttc' => 0],
                'hard' => ['count' => 0, 'avg_completion' => 0, 'avg_ttc' => 0],
            ];

            foreach ($lessonQuizzes as $quiz) {
                $level = strtolower($quiz->difficulty_level ?? 'medium');
                if (isset($difficultyStats[$level])) {
                    $difficultyStats[$level]['count']++;
                    $difficultyStats[$level]['avg_completion'] += $quiz->completion_pct;
                    $difficultyStats[$level]['avg_ttc'] += $quiz->ttc ?? 0;
                }
            }

            // Calculate averages
            foreach ($difficultyStats as $level => &$stats) {
                if ($stats['count'] > 0) {
                    $stats['avg_completion'] = round($stats['avg_completion'] / $stats['count'], 1);
                    $stats['avg_ttc'] = round($stats['avg_ttc'] / $stats['count']);
                }
            }

            $difficultyProgression[$lessonKey] = [
                'unit' => $curriculum->unit,
                'lesson' => $curriculum->lesson,
                'title' => $curriculum->title,
                'total_quizzes' => $lessonQuizzes->count(),
                'difficulty_stats' => $difficultyStats,
                'recommended_difficulty' => $this->calculateRecommendedDifficulty($difficultyStats),
            ];
        }

        // Sort by unit and lesson
        ksort($difficultyProgression);

        return $difficultyProgression;
    }

    private function buildLessonStats($quizzes)
    {
        $lessonStats = [];

        // Get overall lesson performance statistics
        $stats = $quizzes->groupBy('curriculum_id')->map(function ($lessonQuizzes) {
            $curriculum = $lessonQuizzes->first()->curriculum;
            $totalAttempts = $lessonQuizzes->count();
            $avgCompletion = $lessonQuizzes->avg('completion_pct');
            $avgTTC = $lessonQuizzes->avg('ttc');
            $excellentCount = $lessonQuizzes->where('completion_pct', '>=', 80)->count();
            $goodCount = $lessonQuizzes->whereBetween('completion_pct', [60, 79])->count();
            $fairCount = $lessonQuizzes->whereBetween('completion_pct', [40, 59])->count();
            $needsImprovementCount = $lessonQuizzes->where('completion_pct', '<', 40)->count();
            
            return [
                'unit' => $curriculum->unit,
                'lesson' => $curriculum->lesson,
                'title' => $curriculum->title,
                'total_attempts' => $totalAttempts,
                'avg_completion' => round($avgCompletion, 1),
                'avg_ttc' => round($avgTTC),
                'excellent_count' => $excellentCount,
                'good_count' => $goodCount,
                'fair_count' => $fairCount,
                'needs_improvement_count' => $needsImprovementCount,
                'success_rate' => round((($excellentCount + $goodCount) / $totalAttempts) * 100, 1),
                'recommended_difficulty' => $this->calculateRecommendedDifficultyFromStats($avgCompletion),
            ];
        });

        foreach ($stats as $stat) {
            $lessonKey = $stat['unit'] . '_' . $stat['lesson'];
            $lessonStats[$lessonKey] = $stat;
        }

        // Sort by unit and lesson
        ksort($lessonStats);

        return $lessonStats;
    }

    private function buildChartData($quizzes)
    {
        // Group quizzes by month for chart data
        $monthlyData = $quizzes->groupBy(function ($quiz) {
            return $quiz->created_at->format('Y-m');
        })->map(function ($monthQuizzes) {
            return [
                'completion' => round($monthQuizzes->avg('completion_pct'), 1),
                'ttc' => round($monthQuizzes->avg('ttc')),
            ];
        })->sortKeys();

        $labels = [];
        $completionData = [];
        $ttcData = [];

        foreach ($monthlyData as $month => $data) {
            $labels[] = date('M Y', strtotime($month . '-01'));
            $completionData[] = $data['completion'];
            $ttcData[] = $data['ttc'];
        }

        return [
            'labels' => $labels,
            'completion' => $completionData,
            'ttc' => $ttcData,
        ];
    }

    private function calculateRecommendedDifficulty($difficultyStats)
    {
        $totalQuizzes = array_sum(array_column($difficultyStats, 'count'));
        if ($totalQuizzes === 0) return 'medium';

        $avgCompletion = array_sum(array_column($difficultyStats, 'avg_completion')) / $totalQuizzes;
        
        if ($avgCompletion >= 80) return 'hard';
        if ($avgCompletion >= 60) return 'medium';
        return 'easy';
    }

    private function calculateRecommendedDifficultyFromStats($avgCompletion)
    {
        if ($avgCompletion >= 80) return 'hard';
        if ($avgCompletion >= 60) return 'medium';
        return 'easy';
    }
}
