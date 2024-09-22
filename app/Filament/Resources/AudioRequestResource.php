<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AudioRequestResource\Pages;
use App\Models\AudioRequest;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;

class AudioRequestResource extends Resource
{
    protected static ?string $model = AudioRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-musical-note';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('text')
                    ->label('Text')
                    ->required()
                    ->placeholder('Enter text to transcribe')
                    ->maxLength(5000), // Define maximum text length

                Select::make('character')
                    ->label('Character')
                    ->required()
                    ->options([
                        'Hipster' => 'Hipster',
                        'Speedster' => 'Speedster',
                        'Engineer' => 'Engineer',
                        'Shadows' => 'Shadows',
                    ])
                    ->searchable()
                    ->placeholder('Select a character'),

                TextInput::make('file_path')
                    ->label('Audio File Path')
                    ->disabled() // Disabling manual input for file path
                    ->hint('Audio file will be generated automatically.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('character')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->since()->sortable(),
                Tables\Columns\TextColumn::make('file_path')->label('Audio')->url(fn($record) => route('audio.download', $record)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAudioRequests::route('/'),
            'create' => Pages\CreateAudioRequest::route('/create'),
            'edit' => Pages\EditAudioRequest::route('/{record}/edit'),
        ];
    }
}