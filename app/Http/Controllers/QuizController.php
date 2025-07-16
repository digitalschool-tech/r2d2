<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Curriculum;
use App\Actions\CreateNewMissionAction;

class QuizController extends Controller
{
    public function generateQuizFromCurriculum(Request $request)
    {
        try {
            $lesson = $request->input('lesson');
            $unit = $request->input('unit');
            $studentId = $request->input('student_id', 1);

            if (!$lesson || !$unit || !$studentId) {
                return response()->json(['error' => 'Unit, lesson and student_id are required.'], 400);
            }

            Log::info('Generating quiz for:', [
                'unit' => $unit,
                'lesson' => $lesson
            ]);

            $curriculum = Curriculum::where('unit', $unit)
                ->where('lesson', $lesson)
                ->select('id', 'title', 'content', 'pdf_content', 'lesson', 'unit')
                ->first();

            if (!$curriculum) {
                return response()->json(['error' => 'Curriculum not found for given unit and lesson.'], 404);
            }

            $content = $curriculum->content;
            Log::info('Curriculum content length: ' . strlen($content));
            Log::info('Calling quizgen with data:', [
                'unit' => $unit,
                'lesson' => $lesson,
                'content' => $content,
            ]);


            // Call the FastAPI quizgen service
            $quizResponse = Http::timeout(180)->post('http://138.201.173.118:8000/generate_quiz', [ //http://localhost:8080/generate_quiz
                'unit' => $unit,
                'lesson' => $lesson,
                'content' => $content,
                // optional: you can also pass ttc and completion_pct if needed
                // 'ttc' => 180,
                // 'completion_pct' => 70,
            ]);

            if (!$quizResponse->successful()) {
                Log::error('Quiz Generator API failed', [
                    'status' => $quizResponse->status(),
                    'body' => $quizResponse->body()
                ]);
                return response()->json(['error' => 'Quiz generation failed.'], 500);
            }

            $quizData = $quizResponse->json();

            if (!isset($quizData['questions']) || !is_array($quizData['questions']) || count($quizData['questions']) === 0) {
                Log::error('Quiz data missing or invalid', ['quizData' => $quizData]);
                return response()->json(['error' => 'Quiz generation returned no valid questions.'], 500);
            }

            
            $mission = CreateNewMissionAction::handle($content, $studentId, $quizData);
            return response()->json([
                'unit' => $unit,
                'lesson' => $lesson,
                'quiz' => $quizData,
                'mission' => $mission
            ], 200);

            } catch (\Exception $e) {
                Log::error('Error generating quiz: ' . $e->getMessage(), [
                    'exception' => $e
                ]);
                return response()->json(['error' => 'Unexpected error: ' . $e->getMessage()], 500);
        }
    }
}
