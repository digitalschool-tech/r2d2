<?php

namespace App\Actions;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Actions\DeepAction;

class CreateNewMissionAction
{
    /**
     * Call the create-new-mission endpoint using quiz data from DeepAction.
     *
     * @param string $unit
     * @param string $lesson
     * @param int $studentId
     * @return array
     * @throws \Exception
     */
    public static function handle(string $unit, string $lesson, int $studentId): array
    {
        
        $quizData = DeepAction::handle($unit, $lesson);

        $payload = [
            'available_xp'   => 100,
            'player_id'      => $studentId,
            'content'        => [
                'en' => $quizData,
            ],
        ];

        $endpointUrl = 'https://dev-houses-bo.digitalschool.tech/api/create-new-mission';

        try {
            $response = Http::post($endpointUrl, $payload);

            if (!$response->successful()) {
                Log::error("Failed to create new mission", [
                    'endpoint' => $endpointUrl,
                    'status' => $response->status(),
                    'body'    => $response->body(),
                ]);
                throw new \Exception('Mission creation failed: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error("Error in CreateNewMissionAction: {$e->getMessage()}", [
                'unit'     => $unit,
                'lesson'   => $lesson,
                'studentId'=> $studentId,
            ]);
            throw $e;
        }
    }
}
