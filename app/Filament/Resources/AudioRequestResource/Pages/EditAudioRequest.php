<?php

namespace App\Filament\Resources\AudioRequestResource\Pages;

use App\Filament\Resources\AudioRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAudioRequest extends EditRecord
{
    protected static string $resource = AudioRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
