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
            ->select('title', 'content', 'lesson', 'unit', 'file_path')
            ->first();

        $curriculumData = [
            'title' => $curriculum->title,
            'content' => $curriculum->content,
            'lesson' => $curriculum->lesson,
            'unit' => $curriculum->unit,
        ];

        if ($curriculum->file_path) {
            try {
                $filePath = Storage::disk('public')->path($curriculum->file_path);
                if (!file_exists($filePath)) {
                    throw new \Exception("PDF file not found at path: {$filePath}");
                }
                $parser = new Parser();
                $pdf = $parser->parseFile($filePath);
                $curriculumData['file_content'] = $pdf->getText();
            } catch (\Exception $e) {
                \Log::error('PDF parsing failed: ' . $e->getMessage());
                $curriculumData['file_content'] = '';
            }
        }

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
        // Get the path to the template files
        $h5pPath = storage_path('app/private/h5p/');

        // Create a new ZipArchive
        $zip = new ZipArchive();
        $filePath = storage_path('app/private/h5p/generated/' . $filename);

        if ($zip->open($filePath, ZipArchive::CREATE) === TRUE) {
            // Add the h5p.json file to the ZIP
            $zip->addFile($h5pPath . 'h5p.json', 'h5p.json');

            // Add the dynamically generated content to the ZIP under content/content.json
            $zip->addFromString('content/content.json', $content);

            // Close the ZIP file
            $zip->close();
        } else {
            throw new \Exception('Unable to create H5P file at ' . $filePath . '. Check directory permissions.');
        }
    }
}