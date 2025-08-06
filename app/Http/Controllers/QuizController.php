<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Curriculum;
use App\Actions\CreateNewMissionAction;
use App\Models\StudentProfile;
use App\Models\Quiz;

class QuizController extends Controller
{
    public function generateQuizFromCurriculum(Request $request)
    {

        $data = $request->validate([
            'student_id' => 'required|integer',
            'unit' => 'required|string',
            'lesson' => 'required|integer',
        ]);

        $curriculum = Curriculum::where('unit', $data['unit'])
            ->where('lesson', $data['lesson'])
            ->select('id', 'title', 'content', 'pdf_content', 'lesson', 'unit')
            ->first();

        if (!$curriculum) {
            return response()->json(['error' => 'Curriculum not found.'], 404);
        }

        $student = StudentProfile::firstOrCreate([
            'external_student_id' => $data['student_id'],
        ]);

  
        $avgTtc = $student->ttc ?? 120; // Default to 120 seconds if not set
        $avgCompletion = $student->completion_pct ?? 100; // Default to 100% if not set

        $quizResponse = Http::timeout(300)->post('http://138.201.173.118:8000/generate_quiz', [ //http://138.201.173.118:8000/generate_quiz
            'unit' => $data['unit'],
            'lesson' => $data['lesson'],
            'content' => $curriculum->content,
            'ttc' => $avgTtc,
            'completion_pct' => $avgCompletion,
        ]);

        if (!$quizResponse->successful()) {
            Log::error('Quiz Generator API failed', [
                'status' => $quizResponse->status(),
                'body' => $quizResponse->body(),
            ]);

            return response()->json(['error' => 'Quiz generation failed.'], 500);
        }

        $quizData = $quizResponse->json();

        if (empty($quizData['questions'])) {
            return response()->json(['error' => 'No questions returned from quiz generator.'], 422);
        }

         $quiz = Quiz::create([
            'external_student_id' => $data['student_id'],
            'curriculum_id' => $curriculum->id,
            'quiz_data' => $quizData['questions'],
            'difficulty_level' => $quizData['difficulty_level'] ?? 'medium',
            'ttc' => null,
            'completion_pct' => null,
            'performance' => null,
            'wrong_questions' => [],
        ]);

        return response()->json([
            'quiz_id' => $quiz->id,
            'quiz_data' => $quizData['questions'],
            'message' => 'Quiz generated successfully',
        ]);

        $mission = CreateNewMissionAction::handle('content', $data['student_id'], $quizData, $curriculum->title);
     }

    public function submitQuiz(Request $request, Quiz $quiz)
    {
        $request->validate([
            'ttc' => 'required|integer|min:1',
            'completion_pct' => 'required|numeric|min:0|max:100',
            'wrong_questions' => 'nullable|array',
            'performance' => 'nullable|numeric',
        ]);

        $wrongQuestions = $request->input('wrong_questions', []);
        $performance = $request->input('performance');

        $quiz->update([
            'ttc' => $request->ttc,
            'completion_pct' => $request->completion_pct,
            'wrong_questions' => $wrongQuestions,
            'performance' => $performance,
        ]);

        $quiz->student?->updateFromQuiz();

        return response()->json(['message' => 'Quiz submitted successfully.']);
    }

    public function getStudentRadarProfile($studentId)
    {
        $student = StudentProfile::where('external_student_id', $studentId)->firstOrFail();
        $quizzes = Quiz::where('external_student_id', $studentId)
            ->whereNotNull('performance')
            ->get();

        if ($quizzes->isEmpty()) {
            return response()->json(['message' => 'No quiz data found.'], 404);
        }

        $data = [
            'speed' => max(0, min(100, 120 - $quizzes->avg('ttc'))),
            'accuracy' => round($quizzes->avg('completion_pct'), 2),
            'performance' => round($quizzes->avg('performance'), 2),
            'resilience' => round(100 - (
                $quizzes->pluck('wrong_questions')->flatten()->count()
                / max(1, $quizzes->count())
            ) * 10, 2),
        ];

        return response()->json($data);
    }

    // Feature 2: Performance Over Time
    public function getPerformanceOverTime($studentId)
    {
        $quizzes = Quiz::where('external_student_id', $studentId)
            ->whereNotNull('performance')
            ->orderBy('created_at')
            ->get(['performance', 'created_at']);

        return response()->json($quizzes);
    }

    // Feature 3: Wrong Question Summary
    public function getWrongConceptSummary($quizId)
    {
        $quiz = Quiz::findOrFail($quizId);

        $conceptCounts = [];

        foreach ($quiz->wrong_questions as $question) {
            $concept = $question['concept'] ?? 'unknown';
            $conceptCounts[$concept] = ($conceptCounts[$concept] ?? 0) + 1;
        }

        arsort($conceptCounts);
        return response()->json($conceptCounts);
    }

    public function getLessonPerformance($unit, $lesson)
    {
        $curriculum = Curriculum::where('unit', $unit)
            ->where('lesson', $lesson)
            ->firstOrFail();
    
        $quizzes = Quiz::where('curriculum_id', $curriculum->id)
            ->whereNotNull('completion_pct')
            ->get();
    
        if ($quizzes->isEmpty()) {
            return response()->json(['message' => 'No quiz data available for this lesson.'], 404);
        }
    
        $avgCompletion = round($quizzes->avg('completion_pct'), 2);
        $avgTtc = round($quizzes->avg('ttc'), 2);
        $avgPerformance = round($quizzes->avg('performance'), 2);
    
        return response()->json([
            'unit' => $unit,
            'lesson' => $lesson,
            'average_completion_pct' => $avgCompletion,
            'average_ttc' => $avgTtc,
            'average_performance' => $avgPerformance,
            'quiz_count' => $quizzes->count(),
        ]);
    }    
}
