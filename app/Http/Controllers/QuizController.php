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

        if(strtolower($data['unit']) === 'python basic') {
            $data['unit'] = 'python for kids';
        }

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

        $level = $quizData['level'];
        $hardness_score = $quizData['hardness_score'];


         $quiz = Quiz::create([
            'external_student_id' => $data['student_id'],
            'curriculum_id' => $curriculum->id,
            'quiz_data' => $quizData['questions'],
            'difficulty_level' => $level,
            'ttc' => null,
            'completion_pct' => null,
            'performance' => $hardness_score,
            'wrong_questions' => [],
        ]);
        $quiz_id = $quiz->id;

        $mission = CreateNewMissionAction::handle($quiz_id,'content', $data['student_id'], $quizData, $curriculum->title);

        return response()->json([
            'quiz_id' => $quiz->id,
            'mission' => $mission,
        ]);
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



        
    
}
