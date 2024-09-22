<?php

namespace App\Filament\Resources\AudioRequestResource\Pages;

use App\Filament\Resources\AudioRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Http\Controllers\AudioController;
use Illuminate\Http\Request;

class CreateAudioRequest extends CreateRecord
{
    protected static string $resource = AudioRequestResource::class;

    protected function afterCreate(): void
    {
        $audioRequest = $this->record;

        // Use the AudioController logic to generate audio based on the saved request
        $controller = new AudioController();
        $controller->generate(new Request([
            'text' => $audioRequest->text,
            'character' => $audioRequest->character,
        ]));
    }
}
