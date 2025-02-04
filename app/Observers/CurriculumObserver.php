<?php

namespace App\Observers;

use App\Models\Curriculum;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;

class CurriculumObserver
{
    public function saving(Curriculum $curriculum)
    {
        // activates automatically when a new PDF is uploaded
        if ($curriculum->isDirty('file_path') && $curriculum->file_path) {
            try {
                $filePath = Storage::disk('public')->path($curriculum->file_path);
                if (file_exists($filePath)) {
                    $parser = new Parser();
                    $pdf = $parser->parseFile($filePath);
                    $curriculum->pdf_content = $pdf->getText(); //use pdf_content
                }
            } catch (\Exception $e) {
                \Log::error('PDF parsing failed during upload: ' . $e->getMessage());
            }
        }
    }
} 