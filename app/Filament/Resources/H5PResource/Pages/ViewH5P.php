<?php

namespace App\Filament\Resources\H5PResource\Pages;

use App\Filament\Resources\H5PResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;

class ViewH5P extends ViewRecord
{
    protected static string $resource = H5PResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    protected function getPreloadedFormDataRelationships(): array
    {
        return ['curriculum'];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->record->curriculum) {
            $data['curriculum'] = [
                'title' => $this->record->curriculum->title ?? 'N/A',
                'unit' => $this->record->curriculum->unit ?? 'N/A',
                'lesson' => $this->record->curriculum->lesson ?? 'N/A',
                'file_path' => $this->record->curriculum->file_path ?? 'N/A',
                'pdf_content' => $this->record->curriculum->pdf_content ?? 'N/A',
                'content' => $this->record->curriculum->content ?? 'N/A',
                'prompt' => $this->record->curriculum->prompt ?? 'N/A',
            ];
        }

        return $data;
    }
} 