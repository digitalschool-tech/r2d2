<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class FileListWidget extends Widget
{
    protected static string $view = 'filament.widgets.file-list-widget';

    public $files;

    public function mount(): void
    {
        $this->files = collect(Storage::disk('public')->files('h5p/generated'))
            ->map(function ($file) {
                return [
                    'filename'   => basename($file),
                    'path'       => $file,
                    'created_at' => Carbon::createFromTimestamp(Storage::disk('public')->lastModified($file)),
                    'size'       => Storage::disk('public')->size($file),
                ];
            })
            ->sortByDesc('created_at')
            ->values();
    }

    // Helper method to format bytes into a human-readable string
    public static function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
