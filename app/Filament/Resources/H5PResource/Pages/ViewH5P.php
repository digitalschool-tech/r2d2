<?php

namespace App\Filament\Resources\H5PResource\Pages;

use App\Filament\Resources\H5PResource;
use Filament\Resources\Pages\ViewRecord;

class ViewH5P extends ViewRecord
{
    protected static string $resource = H5PResource::class;

    protected function getPreloadedFormDataRelationships(): array
    {
        return ['curriculum'];
    }
} 