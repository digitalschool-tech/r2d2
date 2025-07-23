<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Curriculum;
use App\Actions\CreateNewMissionAction;
// use App\Models\StudentProfile;
// use App\Models\Quiz;

class QuizController extends Controller
{
    public function generateQuizFromCurriculum(Request $request)
    {
        try {
            $lesson = $request->input('lesson');
            $unit = $request->input('unit');
            $studentId = $request->input('student_id', 32597);

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
            $title = $curriculum->title;

            $progressResponse = Http::get("https://dev-api.houses.digitalschool.tech/api/create-new-ai-mission/players/{$studentId}/latest-ai-mission-progress");
            if (!$progressResponse->successful()) {
                Log::warning('Failed to fetch player progress', [
                    'status' => $progressResponse->status(),
                    'body' => $progressResponse->body()
                ]);
            }

            $ttc = null;
            $completionPct = null;

            if ($progressResponse->ok()) {
                $progressData = $progressResponse->json();
                $ttc = $progressData['time_to_complete'] ?? null;
                $completionPct = $progressData['completion_percentage'] ?? null;

                Log::info('Fetched mission progress', [
                    'ttc' => $ttc,
                    'completion_pct' => $completionPct
                ]);
            }


            Log::info('Curriculum content length: ' . strlen($content));
            Log::info('Calling quizgen with data:', [
                'unit' => $unit,
                'lesson' => $lesson,
                'content' => $content,
                'ttc' => $ttc,
                'completion_pct' => $completionPct
            ]);


            // Call the FastAPI quizgen service
            $quizResponse = Http::timeout(300)->post('http://138.201.173.118:8000/generate_quiz', [ //  http://localhost:8080/generate_quiz
                'unit' => $unit,
                'lesson' => $lesson,
                'content' => $content,
                'ttc' => 180,
                'completion_pct' => 70,
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

            // $student = StudentProfile::updateOrCreate(
            //     ['external_student_id' => $studentId],
            //     ['ttc' => $ttc, 'completion_pct' => $completionPct]
            // );

            // // Save quiz data
            // Quiz::create([
            //     'external_student_id' => $studentId,
            //     'curriculum_id' => $curriculum->id,
            //     'questions' => $quizData['questions'],
            //     'student_answers' => [],
            //     'wrong_question_ids' => [],
            //     'ttc' => $ttc,
            //     'completion_pct' => $completionPct,
            // ]);


            $mission = CreateNewMissionAction::handle($content, $studentId, $quizData, $title);
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
