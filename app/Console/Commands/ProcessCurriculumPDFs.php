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
        $content = file_get_contents($filePath);
        
        // Check if content appears to be HTML/XML
        if (preg_match('/<[^>]+>/', $content)) {
            // Use DOM parser for HTML content
            libxml_use_internal_errors(true);
            $dom = new \DOMDocument();
            $dom->loadHTML($content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            
            // Extract only text content
            $text = '';
            $xpath = new \DOMXPath($dom);
            
            // Get all text nodes while excluding style and script tags
            $textNodes = $xpath->query('//text()[not(ancestor::style) and not(ancestor::script)]');
            
            foreach ($textNodes as $node) {
                $nodeText = trim($node->nodeValue);
                if (!empty($nodeText)) {
                    $text .= $nodeText . ' ';
                }
            }
            
            // Clean up the text
            $text = preg_replace('/\s+/', ' ', $text); // Replace multiple spaces with single space
            return trim($text);
        }
        
        // Fall back to regular text extraction for non-HTML
        return trim(strip_tags($content));
    }

    private function extractTextFromNode(\DOMNode $node, &$text) 
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            $text .= $node->nodeValue . ' ';
            return;
        }

        // Preserve certain structural elements
        $block_elements = ['p', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
        
        if (in_array(strtolower($node->nodeName), $block_elements)) {
            $text .= "\n\n";
        }

        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $child) {
                $this->extractTextFromNode($child, $text);
            }
        }
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