<?php

namespace App\Filament\Resources\H5PResource\Pages;

use App\Filament\Resources\H5PResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListH5PS extends ListRecords
{
    protected static string $resource = H5PResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
