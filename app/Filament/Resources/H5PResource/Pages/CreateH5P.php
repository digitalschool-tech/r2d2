<?php

namespace App\Filament\Resources\H5PResource\Pages;

use App\Filament\Resources\H5PResource;
use App\Models\Curriculum;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;
use App\Actions\GPTAction;
use ZipArchive;
use Smalot\PdfParser\Parser;

class CreateH5P extends CreateRecord
{
    protected static string $resource = H5PResource::class;

    protected static string $prompt = '
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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $curriculum = Curriculum::where('id', $data["curriculum_id"])
            ->select('title', 'content', 'pdf_content', 'lesson', 'unit')
            ->first();

        $curriculumData = [
            'title' => $curriculum->title,
            'content' => $curriculum->content . "\n" . $curriculum->pdf_content,
            'lesson' => $curriculum->lesson,
            'unit' => $curriculum->unit,
        ];

        $data["prompt"] = json_encode($curriculumData);

        $gpt = $this->generateContentFromGPT($data["prompt"]);

        $content = $gpt[0];
        $data["prompt"] = $gpt[1];

        // Generate a unique filename
        $filename = 'h5p_' . uniqid() . '.h5p';

        // Store the generated H5P file using ZipArchive
        $this->createH5PFile($filename, $content);

        // Save the filename to the database
        $data['filename'] = $filename;

        return $data;
    }

    protected function generateContentFromGPT(string $prompt)
    {
        $newPrompt = str_replace("%theme%", $prompt, self::$prompt);

        $json = GPTAction::handle($newPrompt);

        // Get the existing content.json from the storage
        $content_structure = json_decode(Storage::disk('local')->get('/h5p/content.json'), true);

        // Replace the questions in the content structure with generated content
        $content_structure["choices"] = json_decode($json, true);

        // Return the updated content JSON
        return [json_encode($content_structure), $json];
    }

    protected function createH5PFile(string $filename, string $content): void
    {
        $h5pPath = storage_path('app/private/h5p/');
        $h5pJsonPath = $h5pPath . 'h5p.json';
    
        // Validate h5p.json exists
        if (!file_exists($h5pJsonPath)) {
            throw new \Exception("h5p.json template missing at: " . $h5pJsonPath);
        }
    
        // Create target directory if missing
        $filePath = storage_path('app/private/h5p/generated/' . $filename);
        $directory = dirname($filePath);
        
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    
        $zip = new ZipArchive();
        
        if ($zip->open($filePath, ZipArchive::CREATE) === TRUE) {
            // Add template files
            $zip->addFile($h5pJsonPath, 'h5p.json');
            
            // Create content directory structure
            $zip->addFromString('content/content.json', $content);
            
            $zip->close();
        } else {
            throw new \Exception('Failed to create H5P file at: ' . $filePath);
        }
    }
}