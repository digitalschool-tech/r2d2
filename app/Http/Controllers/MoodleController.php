<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Http;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Illuminate\Support\Facades\Log;
use App\Models\Curriculum;
use App\Actions\GPTAction;
use ZipArchive;

class MoodleController extends Controller
{
    public function generateH5PAndUpload(Request $request)
    {
        try {
            $lesson = $request->input('lesson');
            $unit = $request->input('unit');
            $courseId = $request->input('course_id', 24);
            $sectionId = $request->input('section_id', 7); 
            
            if (!$lesson || !$unit) {
                return response()->json(['error' => 'Lesson and unit are required.'], 400);
            }
            
            $content = self::findCurriculum($unit, $lesson);
            $filename = 'h5p_' . uniqid() . '.h5p';
            $h5pFilePath = self::createH5PFile($filename, $content);
            

            if (!file_exists($h5pFilePath)) {
                return response()->json(['error' => 'H5P file not found: ' . $h5pFilePath], 400);
            }
    
            $uploadResponse = self::uploadH5PDirectly($h5pFilePath, $courseId, $sectionId, $content);
    
            return response()->json([
                'message' => 'H5P file generated and uploaded successfully.',
                'upload_response' => $uploadResponse
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
    public static function findCurriculum($unit, $lesson)
    {
        $data["prompt"] = json_encode(
            Curriculum::where('unit', $unit)->where('lesson', $lesson)
                ->select('title', 'content', 'lesson', 'unit')
                ->get()
        );

        $gpt = self::generateContentFromGPT($data["prompt"]);

        $content = $gpt[0];
        return $gpt[1];
    }


   public static function uploadH5PDirectly($filePath, $courseId = 24, $sectionId = 7, $prompt)
{
    $moodleApiUrl = 'https://dev-moodle.digitalschool.tech/hello.php';
    $moodleToken = 'ardit';

    // Validate file exists
    if (!file_exists($filePath)) {
        throw new \Exception('H5P file not found at: ' . $filePath);
    }

    // Validate file extension
    if (strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) !== 'h5p') {
        throw new \Exception('Invalid file type. Only .h5p files are allowed.');
    }

    $jsonContent = json_encode([
        "choices" => $prompt,
        "behaviour" => [
            "timeoutCorrect" => 1000,
            "timeoutWrong" => 1000,
            "soundEffectsEnabled" => true,
            "enableRetry" => true,
            "enableSolutionsButton" => true,
            "passPercentage" => 100,
            "autoContinue" => true
        ],
        "l10n" => [
            "showSolutionButtonLabel" => "Show solution",
            "retryButtonLabel" => "Retry", 
            "solutionViewTitle" => "Solution",
            "correctText" => "Correct!",
            "incorrectText" => "Incorrect!",
            "muteButtonLabel" => "Mute feedback sound",
            "closeButtonLabel" => "Close",
            "slideOfTotal" => "Slide :num of :total",
            "nextButtonLabel" => "Next question",
            "scoreBarLabel" => "You got :num out of :total points",
            "solutionListQuestionNumber" => "Question :num",
            "a11yShowSolution" => "Show the solution. The task will be marked with its correct solution.",
            "a11yRetry" => "Retry the task. Reset all responses and start the task over again.",
            "shouldSelect" => "Should have been selected",
            "shouldNotSelect" => "Should not have been selected"
        ],
        "overallFeedback" => [[
            "from" => 0,
            "to" => 100,
            "feedback" => "You got :numcorrect of :maxscore correct"
        ]]
    ]);

    // Create the request using Http facade with multipart form data
    try {
        $fileHandle = fopen($filePath, 'r');
        $response = Http::timeout(30)
            ->withHeaders([
                'Accept' => '*/*',
            ])
            ->attach(
                'h5pfile',
                $fileHandle,
                basename($filePath)
            )
            ->post($moodleApiUrl, [
                'token' => $moodleToken,
                'course' => $courseId,
                'section' => $sectionId,
                'username' => 'dionosmani',
                'password' => 'zmExxi$f#NbSV0GY',
                'jsoncontent' => $jsonContent
            ]);

        // Close file handle
        fclose($fileHandle);

        if ($response->failed()) {
            $error = $response->body();
            throw new \Exception('Failed to upload H5P: ' . $error);
        }

        return response()->json([
            'message' => 'H5P file uploaded successfully.',
            'moodle_response' => $response->json(),
        ]);

    } catch (\Exception $e) {
        // Ensure file handle is closed if still open
        if (isset($fileHandle) && is_resource($fileHandle)) {
            fclose($fileHandle);
        }
        throw $e;
    }
}

    protected static string $prompt = '
        Generate a JSON array with the following structure:
        {
            "question": "What is a gravity well?",
            "answers": [
                "A region of space where the gravitational pull is so strong that objects are drawn toward it",
                "A tunnel through space that allows for faster-than-light travel",
                "A source of magnetic energy in space",
                "A point in space where gravity is absent"
            ],
            "correct": 0
        }

        Using this structure, create 5 questions based on the following lesson data: "%theme%". Each question should contain four options, and the correct answer should be specified by its index in the correct field. The response should only return the JSON array without any additional text or explanations.
    ';

        public static function generateContentFromGPT(string $prompt)
    {
        $newPrompt = str_replace("%theme%", $prompt, self::$prompt);

        $json = GPTAction::handle($newPrompt);

        // Get the existing content.json from the storage
        $content_structure = json_decode(Storage::disk('local')->get('/h5p/content.json'), true);

        // Replace the questions in the content structure with generated content
        $content_structure["choices"] = json_decode($json, true);

        // Return the updated content JSON
        return [json_encode($content_structure), $json];
    }

    public static function createH5PFile(string $filename, string $content)
    {
        // Get the path to the template files
        $h5pPath = storage_path('app/private/h5p/');

        // Create a new ZipArchive
        $zip = new ZipArchive();
        $filePath = storage_path('app/private/h5p/generated/' . $filename);

        if ($zip->open($filePath, ZipArchive::CREATE) === TRUE) {
            // Add the h5p.json file to the ZIP
            $zip->addFile($h5pPath . 'h5p.json', 'h5p.json');

            // Add the dynamically generated content to the ZIP under content/content.json
            $zip->addFromString('content/content.json', $content);

            // Close the ZIP file
            $zip->close();
        } else {
            throw new \Exception('Unable to create H5P file at ' . $filePath . '. Check directory permissions.');
        }

        return $filePath;
    }

}