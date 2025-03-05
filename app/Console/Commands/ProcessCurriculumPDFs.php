<?php

namespace App\Console\Commands;

use App\Models\Curriculum;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;
use PhpOffice\PhpWord\IOFactory;

class ProcessCurriculumPDFs extends Command
{
    protected $signature = 'curriculum:process-documents';
    protected $description = 'Process all curriculum PDFs and DOC files and save content to database';

    private function extractPdfContent(string $filePath): ?string
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($filePath);
        return $pdf->getText();
    }

    private function extractDocContent(string $filePath): ?string
    {
        $phpWord = IOFactory::load($filePath);
        $content = '';
        
        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $content .= $element->getText() . ' ';
                }
            }
        }
        
        return trim($content);
    }

    public function handle()
    {
        $curriculums = Curriculum::whereNotNull('file_path')
            ->whereNull('pdf_content')
            ->get();

        $bar = $this->output->createProgressBar(count($curriculums));
        $this->info('Processing document files...');
        
        foreach ($curriculums as $curriculum) {
            try {
                $filePath = Storage::disk('public')->path($curriculum->file_path);
                if (!file_exists($filePath)) {
                    continue;
                }

                $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                $content = match ($extension) {
                    'pdf' => $this->extractPdfContent($filePath),
                    'doc', 'docx' => $this->extractDocContent($filePath),
                    default => null,
                };

                if ($content) {
                    $curriculum->update([
                        'pdf_content' => $content
                    ]);
                }
            } catch (\Exception $e) {
                $this->error("Failed to process file for curriculum ID {$curriculum->id}: {$e->getMessage()}");
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Document processing completed!');
    }
} 