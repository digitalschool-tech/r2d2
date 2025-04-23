<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use App\Models\Curriculum;
use App\Actions\GPTAction;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use ZipArchive;
use App\Actions\CreateNewMissionAction;
use App\Models\H5P;
use GuzzleHttp\Client;

class MoodleController extends Controller
{
    private string $moodleApiUrl;
    private string $moodleToken;

    public function __construct()
    {
        // Ideally, these should be set in a config file or environment variables.
        $this->moodleApiUrl = 'https://dev-moodle.digitalschool.tech/create_hvp.php';
        $this->moodleToken =  'ardit';
    }

    /**
     * Generate H5P content and upload it to Moodle.
     */
    public function generateH5PAndUpload(Request $request)
    {
        try {
            Log::info('Starting H5P generation and upload process');
            
            $lesson = $request->input('lesson');
            $unit = $request->input('unit');
            $courseId = $request->input('course_id', 100103);
            $sectionId = $request->input('section_id', 1);
            $studentId = $request->input('student_id', 1);

            Log::info('Input parameters', [
                'lesson' => $lesson,
                'unit' => $unit,
                'courseId' => $courseId,
                'sectionId' => $sectionId
            ]);

            // Validate inputs
            if (!$lesson || !$unit) {
                Log::warning('Validation failed: Missing lesson or unit');
                return response()->json(['error' => 'Lesson and unit are required.'], 400);
            }

            // Find the curriculum content
            Log::info('Finding curriculum content');
            $gptContent = $this->findCurriculum($unit, $lesson);
            $content = $this->prepareJsonContent($gptContent);
            Log::error('Content prepared', ['content' => $content, 'gptContent' => $gptContent, 'jsonContent' => json_encode($gptContent)]);
            // Create the H5P file
            Log::info('Creating H5P file');
            $filename = 'h5p_' . uniqid() . '.h5p';
            $h5pFilePath = $this->createH5PFile($filename, $content);

            Log::info('H5P file created', [
                'path' => $h5pFilePath
            ]);

            // Check if the file exists
            if (!file_exists($h5pFilePath)) {
                Log::error('H5P file not found at expected path', ['path' => $h5pFilePath]);
                return response()->json(['error' => 'H5P file not found: ' . $h5pFilePath], 400);
            }

            // Upload H5P file to Moodle
            Log::info('Uploading H5P to Moodle');
            // $uploadResponse = $this->uploadH5PDirectly($h5pFilePath, $courseId, $sectionId, $content, $studentId);

            Log::info('Upload completed successfully', [
                'response' => $uploadResponse ?? ""
            ]);

            $prompt = "Generate quiz questions for Unit: {$request->unit}, Lesson: {$request->lesson}";
            $filename = "quiz_unit_{$request->unit}_lesson_{$request->lesson}_" . time();
            
            $curriculum = Curriculum::where('unit', $unit)
                ->where('lesson', $lesson)
                ->select('id', 'title', 'content', 'lesson', 'unit')
                ->first();

            // Create H5P record with both prompt and filename
            $h5p = H5P::create([
                'curriculum_id' => $curriculum?->id,
                'course_id' => $courseId,
                'section_id' => $sectionId,
                'prompt' => $prompt,
                'filename' => $filename,
                'gpt_response' => json_encode($gptContent),
                'view_url' => $uploadResponse['viewdirecturl'] ?? "",
                'cmid' => $uploadResponse['cmid'] ?? 0,
            ]);

            // CreateNewMissionAction::handle($content, $uploadResponse['viewdirecturl'], $uploadResponse['cmid'], $studentId);
            return response()->json([
                'message' => 'H5P file generated and uploaded successfully.',
                'upload_response' => $uploadResponse ?? ""
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error in generateH5PAndUpload: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Find curriculum data by unit and lesson.
     */
    public function findCurriculum(string $unit, string $lesson)
    {
        // Fetch curriculum data from the database
        $curriculumData = Curriculum::where('unit', $unit)->where('lesson', $lesson)
            ->select('title', 'content', 'lesson', 'unit', 'pdf_content')
            ->first();

        $curriculumData = (!$curriculumData) ? "Lesson: " . $lesson . " Unit: " . $unit : $curriculumData->pdf_content;

        // Generate content from deepseek-r1 model
        return $this->generateContentFromGPT($curriculumData);
    }

    /**
     * Upload the generated H5P file to Moodle.
     */
    public function uploadH5PDirectly(string $filePath, int $courseId, int $sectionId, string $content, int $studentId)
    {
        Log::info('Starting direct H5P upload');

        $this->validateH5PFile($filePath);

        try {
            $fileResource = fopen($filePath, 'r');
            if ($fileResource === false) {
                Log::error('Unable to open file for reading', ['filePath' => $filePath]);
                throw new \Exception('Unable to open file: ' . $filePath);
            }

            $response = Http::timeout(30)
                ->withHeaders([
                    'Accept' => '*/*',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
                ])
                ->withCookies([
                    'MoodleSession' => env('MOODLE_SESSION_COOKIE', 'dvjumqbpuuvmrf49gcovs8l4th')
                ], parse_url($this->moodleApiUrl, PHP_URL_HOST))
                ->attach(
                    'h5pfile',
                    $fileResource,
                    basename($filePath),
                    ['Content-Type' => 'application/octet-stream']
                )
                ->asMultipart()
                ->post($this->moodleApiUrl, [
                    'token' => $this->moodleToken,
                    'course' => $courseId,
                    'section' => $sectionId,
                    'username' => env('MOODLE_USERNAME', 'Dionosmani'),
                    'password' => env('MOODLE_PASSWORD', 'zmExxi$f#NbSV0GY'),
                    'jsoncontent' => $content
                ]);

            fclose($fileResource);

            if ($response->failed()) {
                Log::error('Upload failed', [
                    'response' => $response->body(),
                    'status' => $response->status()
                ]);
                throw new \Exception('Failed to upload H5P: ' . $response->body());
            }
            $data = $response->json();
            
            return $data;

        } catch (\Exception $e) {
            Log::error('Error uploading H5P to Moodle: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Validate the H5P file.
     */
    private function validateH5PFile(string $filePath)
    {
        if (!file_exists($filePath)) {
            throw new FileNotFoundException('H5P file not found at: ' . $filePath);
        }

        if (strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) !== 'h5p') {
            throw new \Exception('Invalid file type. Only .h5p files are allowed.');
        }
    }

    /**
     * Prepare the JSON content for the H5P upload.
     */
    private function prepareJsonContent($content)
    {
        $jsonContent = json_encode([
            "choices" => $content,
            "behaviour" => [
                "timeoutCorrect" => 1000,
                "timeoutWrong" => 1000,
                "soundEffectsEnabled" => true,
                "enableRetry" => true,
                "enableSolutionsButton" => true,
                "passPercentage" => 100,
                "autoContinue" => true
            ],
            "l10n" => $this->getLocalizationData(),
            "overallFeedback" => [[
                "from" => 0,
                "to" => 100,
                "feedback" => "You got :numcorrect of :maxscore correct"
            ]]
        ]);

        return $jsonContent;
    }

    /**
     * Get the localization data for the H5P content.
     */
    private function getLocalizationData(): array
    {
        return [
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
        ];
    }

    /**
     * Generate content using deepseek-r1 model.
     */
    public function generateContentFromGPT(string $prompt, string $curriculum = '', string $lessonTitle = '')
{
    $deepseekPrompt = <<<PROMPT
    **You are an expert quiz generator for students aged 8–18. Based on the following instructor-facing lesson, generate a quiz**

---

**MUST FOLLOW:**
1. **Language Rules:**
   - Use words from this lesson only
   - Include 2 "Which is NOT..." questions

2. **Question Types:**
   - 7 Multiple Choice:
     * Exactly 4 answer options
   - 3 True/False:
     * Answers MUST be ["True", "False"] exactly
     * Focus on clear facts from lesson

3. **Content Safety:**
   - Only use examples that appear in the lesson
   - **DO NOT** mention concepts outside of the lesson

4. **General Questions:**
   - Focus on main topics from the lesson
   - Ask broad, general questions about these topics
   - Ensure questions are distinct and not rephrased versions of each other

**Example Format (for structure only):**

```json
[
  {
    "question": "What is a program?",
    "answers": [
      "A fun drawing",
      "A robot’s battery",
      "An algorithm that a computer can follow",
      "A puzzle with no rules"
    ],
    "correct": 2,
    "subContentId": "question-1"
  },
  {
    "question": "True or False: A sequence runs from top to bottom in programming.",
    "answers": ["True", "False"],
    "correct": 0,
    "subContentId": "question-2"
  }
]
PROMPT;

    Log::error('Deepseek Prompt:', [
        'prompt' => $deepseekPrompt,
        'curriculum' => $curriculum,
        'lessonTitle' => $lessonTitle
    ]);

    try {
        $response = Http::timeout(60)->post('178.132.223.50:11434/api/generate', [
            'model' => 'deepseek-r1:8b',
            'prompt' => $deepseekPrompt,
            'stream' => false
        ]);

        if ($response->successful()) {
            $responseData = $response->json();

            if (isset($responseData['response'])) {
                $responseContent = $responseData['response'];

                Log::error('Deepseek Raw Response:', ['response' => $responseContent]);

                $json = $this->extractJsonFromResponse($responseContent);

                Log::error('Deepseek Parsed JSON:', ['json' => $json]);

                return $json;
            }
        }

        Log::error('Deepseek API error:', [
            'status' => $response->status(),
            'response' => $response->body()
        ]);

        return '';
    } catch (\Exception $e) {
        Log::error('Deepseek API exception:', [
            'message' => $e->getMessage()
        ]);

        return '';
    }
}


private function extractJsonFromResponse(string $responseText)
{
    $cleaned = preg_replace('/```json|```|\n/', '', $responseText);
    $cleaned = trim($cleaned);

    $decoded = json_decode($cleaned, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        return $decoded;
    }

    if (preg_match('/(\[\s*{.*?}\s*\])/s', $cleaned, $matches)) {
        $fallbackDecoded = json_decode($matches[1], true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $fallbackDecoded;
        }
    }

    throw new \Exception("No valid JSON array found in GPT response.");
}


    /**
     * Create the H5P file from content.
     */
    public function createH5PFile(string $filename, string $content): string
    {
        $h5pPath = storage_path('app/public/h5p/');
        $generatedPath = $h5pPath . 'generated/';

        // Ensure the generated directory exists
        if (!is_dir($generatedPath)) {
            if (!mkdir($generatedPath, 0755, true)) {
                throw new \Exception('Unable to create directory: ' . $generatedPath);
            }
        }

        $filePath = $generatedPath . $filename;
        Log::info('File path', [
            'path' => $filePath,
            'h5pPath' => $h5pPath
        ]);

        $zip = new ZipArchive();

        if ($zip->open($filePath, ZipArchive::CREATE) !== true) {
            throw new \Exception('Unable to create H5P file at ' . $filePath . '. Check directory permissions.');
        }

        // Add the base H5P json file. Ensure it exists.
        $baseJson = $h5pPath . 'h5p.json';
        if (!file_exists($baseJson)) {
            throw new \Exception('Missing base h5p.json file at ' . $baseJson);
        }
        $zip->addFile($baseJson, 'h5p.json');

        // Add the generated content
        $zip->addFromString('content/content.json', $content);
        $zip->close();

        return $filePath;
    }
}
