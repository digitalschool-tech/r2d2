<?php

namespace App\Console\Commands;

use App\Models\Curriculum;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;

class ProcessCurriculumPDFs extends Command
{
    protected $signature = 'curriculum:process-pdfs';
    protected $description = 'Process all curriculum PDFs and save OCR content to database';

    public function handle()
    {
        $curriculums = Curriculum::whereNotNull('file_path')
            ->whereNull('pdf_content')
            ->get();

        $parser = new Parser();
        $bar = $this->output->createProgressBar(count($curriculums));

        $this->info('Processing PDF files...');
        
        foreach ($curriculums as $curriculum) {
            try {
                $filePath = Storage::disk('public')->path($curriculum->file_path);
                if (file_exists($filePath)) {
                    $pdf = $parser->parseFile($filePath);
                    $content = $pdf->getText();
                    
                    $curriculum->update([
                        'pdf_content' => $content
                    ]);
                }
            } catch (\Exception $e) {
                $this->error("Failed to process PDF for curriculum ID {$curriculum->id}");
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('PDF processing completed!');
    }
} 