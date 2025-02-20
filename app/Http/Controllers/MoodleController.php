<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Http;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

class MoodleController extends Controller
{
    public static function uploadH5PActivity($courseId, $filePath, $grade, $name, $intro) {
        
        // Moodle API configuration
        $moodleApiUrl = env('MOODLE_API_URL');
        $moodleToken = env('MOODLE_API_TOKEN');

        // Get the file path and ensure the file exists
        if (!Storage::exists($filePath)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        $fullFilePath = Storage::path($filePath); // Get absolute path to the file
        $fileName = basename($fullFilePath); // Get the file name
        
        // Get the context ID for the course (this should be a valid context ID in Moodle)
        $contextid = self::getCourseContextId($courseId);

        if (!$contextid) {
            return response()->json(['message' => 'Invalid course ID or context ID'], 400);
        }

        // Step 1: Upload the H5P file to Moodle using core_files_upload
        $response = Http::asMultipart()->post($moodleApiUrl, [
            'wstoken' => $moodleToken,
            'wsfunction' => 'core_files_upload',
            'moodlewsrestformat' => 'json',
            'contextid' => $contextid,
            'component' => 'mod_hvp',  // H5P component
            'filearea' => 'package',  // H5P file area
            'itemid' => 0,
            'filepath' => '/',  // Root directory
            'filename' => $fileName,
            'filecontent' => Storage::disk("local")->get($filePath),  // Send the file content
        ]);

        $fileData = $response->json();

        // Check if the file was uploaded successfully
        if (empty($fileData['itemid'])) {
            return response()->json(['message' => 'File upload failed', 'error' => $fileData], 500);
        }

        // Use the uploaded file itemid to create the H5P activity
        $fileid = $fileData['itemid'];

        // Step 2: Add H5P activity to the course
        $addResponse = Http::post($moodleApiUrl, [
            'wstoken' => $moodleToken,
            'wsfunction' => 'mod_hvp_add_instance',  // Custom function for adding H5P activity
            'moodlewsrestformat' => 'json',
            'courseid' => $courseId,
            'name' => $name,
            'intro' => $intro,
            'grade' => $grade,
            'fileid' => $fileid,  // The file id from the uploaded file
        ]);

        $addData = $addResponse->json();

        // Check if the H5P activity was created successfully
        if (isset($addData['exception'])) {
            return response()->json(['message' => 'Failed to create H5P activity', 'error' => $addData], 500);
        }

        return response()->json(['message' => 'H5P activity created successfully', 'data' => $addData]);
    }

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
            
            $curriculum = $this->findCurriculum($unit, $lesson);
    
            $h5pFilePath = $this->generateH5PFile($curriculum, $lesson, $unit);
            if (!file_exists($h5pFilePath)) {
                return response()->json(['error' => 'H5P file not found: ' . $h5pFilePath], 400);
            }
    
            $uploadResponse = $this->uploadH5PDirectly($h5pFilePath, $courseId, $sectionId, $curriculum, $lesson, $unit);
    
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
    private function findCurriculum($unit, $lesson)
    {
        return "DerivedCurriculum_" . $unit . "_" . $lesson;
    }

    private function generateH5PFile($curriculum, $lesson, $unit)
    {
        $directoryPath = storage_path('app/private/h5p/generated');
        
        if (!is_dir($directoryPath)) {
            mkdir($directoryPath, 0777, true); 
        }
        
        $filePath = $directoryPath . '/' . $curriculum . '_' . $lesson . '_' . $unit . '.h5p';
        
        file_put_contents($filePath, 'Fake H5P content for ' . $curriculum . ' - ' . $lesson . ' - ' . $unit);
        
        return $filePath;
    }

   public static function uploadH5PDirectly($filePath, $courseId = 24, $sectionId = 7, $prompt)
{
    $moodleApiUrl = env('MOODLE_API_URL') . 'hello.php';
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

    /**
     * Get the context ID of a course.
     *
     * @param int $courseId
     * @return int
     */
    private static function getCourseContextId($courseId)
    {
        // This function should call Moodle API to get the context ID for the given course ID
        // Moodle's API `core_course_get_courses` can be used to fetch course information, which includes the context ID.
        $client = new Client();
        $response = $client->request('POST', env('MOODLE_API_URL'), [
            'query' => [
                'wstoken' => env('MOODLE_API_TOKEN'),
                'wsfunction' => 'core_course_get_courses',
                'moodlewsrestformat' => 'json',
                'options[ids][0]' => $courseId
            ]
        ]);
    
        $courseData = json_decode($response->getBody()->getContents());
    
        if (!empty($courseData) && isset($courseData[0]->contextid)) {
            return $courseData[0]->contextid;
        }
    
        return 1; // Default context ID if not found
    }
}