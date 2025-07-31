<?php

namespace App\Actions;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Actions\DeepAction;
/**
 * Action class to call the createNewMission endpoint with hardcoded data.
 */
class CreateNewMissionAction
{
    // /**
    //  * Call the createNewMission endpoint using hardcoded endpoint URL and mission data.
    //  *
    //  * @return array The decoded JSON response from the API.
    //  *
    //  * @throws \Exception If the HTTP request fails or returns an error.
    //  */
    // public static function handle(string $content, int $studentId, array $quizData): array
    // {
    //     $prompt = <<<PROMPT
    //     You are an AI mission generator. Based on the following content, return a valid JSON object with the following fields:
    //     - name (string)
    //     - title (string)
    //     - content (string or object)
    //     - summary (string)
    //     The content is:
    //     $content
    //     PROMPT;
    //     // $gpt_data = DeepAction::handle($prompt);
    //     // Log::info('GPT data received in CreateNewMissionAction', ['gpt_data' => $gpt_data]);
    //     // Hardcoded endpoint URL and mission data.
    //     $endpointUrl = 'https://dev-api.houses.digitalschool.tech/api/create-new-ai-mission'; // Replace with your actual endpoint URL.
    //     // $data = (array) json_decode($gpt_data, true);
    //     Log::info('Decoded GPT data in CreateNewMissionAction', ['data' => $data]);

    //     if (!is_array($data)) {
    //         Log::error('DeepAction returned invalid JSON', ['response' => $gpt_data]);
    //         throw new \Exception('Invalid GPT data: not JSON or not array');
    //     }

    //     // // Remove broken/empty 'content' if present
    //     // if (isset($data['content'])) {
    //     //     unset($data['content']);
    //     // }

    //     // Replace with localized content
    //     $data['content'] = [
    //         'en' => $content
    //     ];
    //     // $data['assignment_url'] = $assignment_url;
    //     // $data['content_id'] = $cmid;
    //     // $data['available_xp'] = 100;
    //     $data['player_id'] = $studentId;
    //     // $content = $data['content'];
    //     $data['quiz_data'] = $quizData;
    //     $data['name'] = $data['name'] ?? 'Default Mission Name'; // ensure name is set and not empty
    //     try {
    //         $response = Http::post($endpointUrl, $data);

    //         if (!$response->successful()) {
    //             Log::error("Failed to create new mission. Endpoint: {$endpointUrl}. Response: {$response->body()}");
    //             throw new \Exception('HTTP request failed: ' . $response->body());
    //         }

    //         return $response->json();
    //     } catch (\Exception $e) {
    //         Log::error("Error in CreateNewMissionAction: {$e->getMessage()}");
    //         throw $e;
    //     }
    // }
     /**
     * Call the createNewMission endpoint using direct mission data.
     *
     * @param string $content
     * @param int $studentId
     * @param array $quizData
     * @param string $name
     * @param string $title
     * @return array The decoded JSON response from the API.
     * @throws \Exception If the HTTP request fails or returns an error.
     */
    public static function handle(int $id, string $content, int $studentId, array $quizData, string $name): array
    {
        $endpointUrl = 'https://dev-api.houses.digitalschool.tech/api/create-new-ai-mission';

        $data = [
            'quiz_id' => $id,
            'name' => $name,
            'title' => $name,
            'content' => [
                'en' => $content,
            ],
            'player_id' => $studentId,
            'quiz_data' => $quizData,
        ];

        try {
            $response = Http::post($endpointUrl, $data);

            if (!$response->successful()) {
                Log::error("Failed to create new mission. Endpoint: {$endpointUrl}. Response: {$response->body()}");
                throw new \Exception('HTTP request failed: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error("Error in CreateNewMissionAction: {$e->getMessage()}");
            throw $e;
        }
    }
}
