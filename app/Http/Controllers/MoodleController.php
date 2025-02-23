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

class MoodleController extends Controller
{
    private string $moodleApiUrl;
    private string $moodleToken;

    public function __construct()
    {
        // Ideally, these should be set in a config file or environment variables.
        $this->moodleApiUrl = env('MOODLE_API_URL', 'https://dev-moodle.digitalschool.tech/hello.php');
        $this->moodleToken = env('MOODLE_API_TOKEN', 'ardit');
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
            $courseId = $request->input('course_id', 24);
            $sectionId = $request->input('section_id', 7);

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
            $slimContent = $this->findCurriculum($unit, $lesson);
            $content = $this->prepareJsonContent($slimContent);

            // Create the H5P file
            Log::info('Creating H5P file');
            $filename = 'h5p_' . uniqid() . '.h5p';
            $h5pFilePath = $this->createH5PFile($filename, $content);

            Log::info('H5P file created', [
                'path' => $h5pFilePath
            ]);

            // Check if the file exists
            if (!file_exists($h5pFilePath)) {
                return response()->json(['error' => 'H5P file not found: ' . $h5pFilePath], 400);
            }

            // Upload H5P file to Moodle
            Log::info('Uploading H5P to Moodle');
            $uploadResponse = $this->uploadH5PDirectly($h5pFilePath, $courseId, $sectionId, $content, $slimContent);

            Log::info('Upload completed successfully', [
                'response' => $uploadResponse
            ]);

            return response()->json([
                'message' => 'H5P file generated and uploaded successfully.',
                'upload_response' => $uploadResponse
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
    public function findCurriculum(string $unit, string $lesson): string
    {
        // Fetch curriculum data from the database
        $curriculumData = Curriculum::where('unit', $unit)->where('lesson', $lesson)
            ->select('title', 'content', 'lesson', 'unit')
            ->first();

        if (!$curriculumData) {
            throw new \Exception('Curriculum not found for unit: ' . $unit . ', lesson: ' . $lesson);
        }

        // Generate content from GPT
        return $this->generateContentFromGPT($curriculumData->content);
    }

    /**
     * Upload the generated H5P file to Moodle.
     */
    public function uploadH5PDirectly(string $filePath, int $courseId, int $sectionId, string $content)
    {
        Log::info('Starting direct H5P upload', [
            'courseId' => $courseId,
            'sectionId' => $sectionId,
            'content' => $content
        ]);

        // Validate the H5P file
        $this->validateH5PFile($filePath);
        Log::info('H5P file validated');

        // Create the request payload
        Log::warning('JSON content', [
            'content' => $content,
            'slimContent' => $slimContent
        ]);
        
        try {
            Log::info('Sending upload request to Moodle');
            
            // Use file contents directly instead of file handle
            $fileContents = file_get_contents($filePath);
            
            $response = Http::timeout(30)
                ->withHeaders(['Accept' => '*/*'])
                ->attach(
                    'h5pfile',
                    $fileContents,
                    basename($filePath),
                    ['Content-Type' => 'application/octet-stream']
                )
                ->post($this->moodleApiUrl, [
                    'token' => $this->moodleToken,
                    'course' => $courseId,
                    'section' => $sectionId,
                    'username' => env('MOODLE_USERNAME', 'dionosmani'),
                    'password' => env('MOODLE_PASSWORD', 'zmExxi$f#NbSV0GY'),
                    'jsoncontent' => $content
                ]);

            if ($response->failed()) {
                Log::error('Upload failed', [
                    'response' => $response->body()
                ]);
                throw new \Exception('Failed to upload H5P: ' . $response->body());
            }

            Log::info('Upload successful');
            return $response->json();

        } catch (\Exception $e) {
            Log::error('Error uploading H5P to Moodle: ' . $e->getMessage(), [
                'exception' => $e
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
    private function prepareJsonContent(string $content): string
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
     * Generate content using GPT.
     */
    public function generateContentFromGPT(string $prompt)
    {
        $gptPrompt = str_replace("%theme%", $prompt, $this->getGptPrompt());
        $json = GPTAction::handle($gptPrompt);

        return json_decode($json, true);
    }

    /**
     * Get the GPT prompt template.
     */
    private function getGptPrompt(): string
    {
        return '
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
    }

    /**
     * Create the H5P file from content.
     */
    public function createH5PFile(string $filename, string $content): string
    {
        $h5pPath = storage_path('app/public/h5p/');
        $filePath = storage_path('app/public/h5p/generated/' . $filename);
        Log::info('File path', [
            'path' => $filePath,
            'h5pPath' => $h5pPath
        ]);
        // Create a new ZipArchive instance and add files
        $zip = new ZipArchive();

        if ($zip->open($filePath, ZipArchive::CREATE) !== true) {
            throw new \Exception('Unable to create H5P file at ' . $filePath . '. Check directory permissions.');
        }

        $zip->addFile($h5pPath . 'h5p.json', 'h5p.json');
        $zip->addFromString('content/content.json', $content);
        $zip->close();

        return $filePath;
    }
}
