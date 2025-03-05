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
        // Try using antiword for .doc files
        $content = shell_exec("antiword " . escapeshellarg($filePath));
        
        if (empty($content)) {
            // Fallback to trying PhpWord if antiword fails
            try {
                $phpWord = IOFactory::load($filePath);
                $content = '';
                
                foreach ($phpWord->getSections() as $section) {
                    foreach ($section->getElements() as $element) {
                        if (method_exists($element, '__toString')) {
                            $content .= (string)$element . ' ';
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->warn("PhpWord fallback failed: " . $e->getMessage());
                return null;
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
                    $this->warn("File not found for curriculum ID {$curriculum->id}: {$filePath}");
                    continue;
                }

                $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                
                // Log file type for debugging
                $this->info("Processing curriculum ID {$curriculum->id} - File type: {$extension}");
                
                // Skip unsupported file types
                if (!in_array($extension, ['pdf', 'doc', 'docx'])) {
                    $this->warn("Unsupported file type ({$extension}) for curriculum ID {$curriculum->id}");
                    continue;
                }

                $content = null;
                if ($extension === 'pdf') {
                    $content = $this->extractPdfContent($filePath);
                } elseif (in_array($extension, ['doc', 'docx'])) {
                    $content = $this->extractDocContent($filePath);
                }

                if ($content) {
                    $curriculum->update([
                        'pdf_content' => $content
                    ]);
                    $this->info("Successfully processed curriculum ID {$curriculum->id}");
                }
            } catch (\Exception $e) {
                $this->error("Failed to process file for curriculum ID {$curriculum->id}: {$e->getMessage()}");
                $this->error("File path: {$filePath}");
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Document processing completed!');
    }
} 