<?php

namespace App\Filament\Resources\AudioRequestResource\Pages;

use App\Filament\Resources\AudioRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAudioRequests extends ListRecords
{
    protected static string $resource = AudioRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
