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
    /**
     * Call the createNewMission endpoint using hardcoded endpoint URL and mission data.
     *
     * @return array The decoded JSON response from the API.
     *
     * @throws \Exception If the HTTP request fails or returns an error.
     */
    public static function handle(string $content, int $studentId, array $quizData): array
    {
        $gpt_data = DeepAction::handle('Return a JSON object with the following fields: name, title, content, summary and based on the content, create a mission that is relevant to the content: ' . $content);
        // Hardcoded endpoint URL and mission data.
        $endpointUrl = 'https://dev-houses-bo.digitalschool.tech/api/create-new-mission'; // Replace with your actual endpoint URL.
        $data = (array) json_decode($gpt_data, true);

        if (!is_array($data)) {
            Log::error('DeepAction returned invalid JSON', ['response' => $gpt_data]);
            throw new \Exception('Invalid GPT data: not JSON or not array');
        }

        // Remove broken/empty 'content' if present
        if (isset($data['content'])) {
            unset($data['content']);
        }

        // Replace with localized content
        $data['content'] = [
            'en' => $content
        ];
        // $data['assignment_url'] = $assignment_url;
        // $data['content_id'] = $cmid;
        // $data['available_xp'] = 100;
        $data['player_id'] = $studentId;
        // $content = $data['content'];

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
