<?php

namespace App\Actions;

use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Log;

/**
 * Action class making GPT call using OpenAI.
 */
class GPTAction
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
            $response = OpenAI::chat()->create([
                'model' => 'gpt-3.5-turbo-16k',
                'max_tokens' => 4000,
                'messages' => [ ['role' => 'user', 'content' => $prompt] ]
            ]);

            if (!isset($response->choices[0]->message->content)) {
                throw new \Exception('Invalid response structure from API.');
            }

            return $response->choices[0]->message->content;
        } catch (\Exception $e) {
            Log::error("Failed to generate commit time: {$e->getMessage()}");
            throw $e;
        }
    }
}
