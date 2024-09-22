<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google\Cloud\TextToSpeech\V1\TextToSpeechClient;
use Illuminate\Support\Facades\Storage;
use App\Models\AudioRequest;

class AudioController extends Controller
{

    public function generate(Request $request)
    {
        $request->validate([
            'text' => 'required|string',
            'character' => 'required|in:Hipster,Speedster,Engineer,Shadows',
        ]);

        $text = $request->input('text');
        $character = $request->input('character');

        // vars: Get the voice name from config
        $voiceName = config("tts.voices.$character");

        // Initialize the TTS client
        $client = new TextToSpeechClient([
            'credentials' => storage_path('app/private/houses-1577353443264-7ef55b725384.json')
        ]);

        // Set up the synthesis input
        $synthesisInput = (new \Google\Cloud\TextToSpeech\V1\SynthesisInput())
            ->setText($text);

        // Build the voice request
        $voice = (new \Google\Cloud\TextToSpeech\V1\VoiceSelectionParams())
            ->setLanguageCode('en-US')
            ->setName($voiceName);

        // Select the audio file type
        $audioConfig = (new \Google\Cloud\TextToSpeech\V1\AudioConfig())
            ->setAudioEncoding(\Google\Cloud\TextToSpeech\V1\AudioEncoding::MP3);

        // Perform the text-to-speech request
        $response = $client->synthesizeSpeech($synthesisInput, $voice, $audioConfig);

        // Get the audio content
        $audioContent = $response->getAudioContent();

        // Save the audio file
        $fileName = 'audio/' . uniqid() . '.mp3';
        Storage::put($fileName, $audioContent);

        $audioRequest = AudioRequest::create([
            'text' => $text,
            'character' => $character,
            'file_path' => $fileName,
        ]);

        // Return the audio file as a response
        return response()->download(storage_path('app/private/' . $fileName))->deleteFileAfterSend(true);
    }
}
