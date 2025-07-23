<?php

namespace App\Actions;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
/**
 * Action class making GPT call using OpenAI.
 */
class DeepAction
{

    public static function handle(string $prompt)
{
    try {
            $response = HTTP::post('http://178.132.223.50:11434/api/generate', [ //  http://10.1.210.200:11434/api/generate
            'model' => 'deepseek-r1:14b',
            'prompt' => $prompt,
            'stream' => false
        ]);

        $raw = $response->getBody()->getContents();
        Log::info('Deepseek Raw Response:', [
            'raw' => $raw
        ]);
        $json = json_decode($raw, true);
        $text = $json['response'] ?? '';

        // Remove <think>...</think> blocks
        $cleaned = preg_replace('/<think>.*?<\/think>/is', '', $text);
        $cleaned = trim($cleaned);

        Log::info('Deepseek Cleaned Response:', [
            'cleaned' => $cleaned
        ]);

        return $cleaned;
    } catch (\Exception $e) {
        Log::error('Deepseek API error:', [
            'error' => $e->getMessage(),
            'prompt' => $prompt
        ]);
        throw $e;
    }
}
    

    // public static function handle(string $prompt)
    // {
    //     try {
    //         $response = HTTP::post('http://10.1.210.200:11434/api/generate', [ //  http://178.132.223.50:11434/api/generate
    //             'json' => [
    //                 'model' => 'deepseek-r1:14b',
    //                 'prompt' => $prompt
    //             ]
    //         ]);

    //         $result = $response->getBody()->getContents();
            
    //         // Log the API response
    //         Log::info('Deepseek Response:', [
    //             'response' => $result
    //         ]);
            
    //         return $result['response'] ?? '[]';
    //     } catch (\Exception $e) {
    //         Log::error('Deepseek API error:', [
    //             'error' => $e->getMessage(),
    //             'prompt' => $prompt
    //         ]);
    //         throw $e;
    //     }
    // }
    // public static function handle(string $unit, string $lesson)
    // {

    //     try {
    //          $response = Http::post('http://138.201.173.118:8000/generate_quiz', [
    //         'unit' =>$unit,
    //         'lesson' => $lesson
    //     ]);

    //       if ($response->successful()) {
    //         return $response->json(); 
    //     } else {
    //         throw new Exception("Failed to generate quiz: " . $response->body());
    //     }
    //     }
    //     catch(Exception $e) {
    //         throw $e;

    //     }
    // }
}
