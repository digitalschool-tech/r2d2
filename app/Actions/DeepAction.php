<?php

namespace App\Actions;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
/**
 * Action class making GPT call using OpenAI.
 */
class DeepAction
{
    /**
     * Handle the generation of commit time using a prompt.
     *
     * @param string $prompt The prompt for the AI model.
     * @return string The content from the AI response.
     * @throws \Exception If the API call fails or does not return expected results.
     */
    public static function handle(string $prompt): string
    {
        try {
            $response = HTTP::post('178.132.223.50:11434/api/generate', [
                'json' => [
                    'model' => 'deepseek-r1:8b',
                    'prompt' => $prompt
                ]
            ]);

            $result = json_decode($response->getBody()->getContents(), true);
            
            // Log the API response
            Log::info('Deepseek Response:', [
                'response' => $result
            ]);
            
            return json_decode($result['response'] ?? '[]', true);
        } catch (\Exception $e) {
            Log::error('Deepseek API error:', [
                'error' => $e->getMessage(),
                'prompt' => $prompt
            ]);
            throw $e;
        }
    }
}
