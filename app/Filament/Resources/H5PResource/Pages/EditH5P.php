<?php

namespace App\Filament\Resources\H5PResource\Pages;

use App\Filament\Resources\H5PResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditH5P extends EditRecord
{
    protected static string $resource = H5PResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
