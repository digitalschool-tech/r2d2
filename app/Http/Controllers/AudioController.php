<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\AudioRequest;
use Log;

class AudioController extends Controller
{
    public function generate(Request $request)
    {
        set_time_limit(300);  // Adjust this value as needed

        $request->validate([
            'text' => 'required|string',
            'character' => 'required|in:Hipster,Speedster,Engineer,Shadows',
        ]);

        $text = $request->input('text');
        $character = $request->input('character');

        // Map characters to Mozilla TTS voice models (random English ones)
        $modelOptions = [
            'Hipster' => 'tts_models/en/ljspeech/tacotron2-DDC',
            'Speedster' => 'tts_models/en/vctk/vits',
            'Engineer' => 'tts_models/en/ljspeech/fast_pitch',
            'Shadows' => 'tts_models/en/jenny/jenny',
        ];

        // Get the TTS model for the selected character
        $model = $modelOptions[$character];

        // Generate a unique filename for the audio output
        $fileName = 'audio/' . uniqid() . '.wav';
        $filePath = storage_path('app/public/' . $fileName);

        // Ensure the directory exists
        Storage::makeDirectory('audio');

        // Build the TTS command using the selected model
        $command = 'tts --text ' . escapeshellarg($text) . ' '
            . '--model_name ' . escapeshellarg($model) . ' '
            . '--out_path ' . escapeshellarg($filePath) . ' ';
            // . '--audio_format mp3';  // Ensure MP3 output format

        // Execute the command
        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            return response()->json(['error' => 'Failed to generate audio'], 500);
        }

        // Store the request details in the database
        $audioRequest = AudioRequest::create([
            'text' => $text,
            'character' => $character,
            'file_path' => $fileName,
        ]);

        // Return the public URL for the generated MP3 file
        $publicUrl = url('storage/public/' . $fileName);  // Ensure public access to storage
        return response()->json(['file_url' => $publicUrl]);
    }
}