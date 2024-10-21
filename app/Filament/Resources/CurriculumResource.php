<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CurriculumResource\Pages;
use App\Filament\Resources\CurriculumResource\RelationManagers;
use App\Models\Curriculum;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Storage;

class CurriculumResource extends Resource
{
    protected static ?string $model = Curriculum::class;
    protected static ?string $navigationGroup = 'Content';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                
                Textarea::make('content')
                    ->required(),
                
                TextInput::make('lesson')
                    ->required()
                    ->maxLength(255),
                
                TextInput::make('unit')
                    ->required()
                    ->maxLength(255),

                FileUpload::make('file_path')  // File upload for curriculum attachment
                    ->label('File Upload')
                    ->directory('curriculum-files')
                    ->visibility('private')
                    ->required(), // If the file is required, otherwise you can remove this line
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title'),
                Tables\Columns\TextColumn::make('lesson'),
                Tables\Columns\TextColumn::make('unit'),
                Tables\Columns\TextColumn::make('file_path') // Optionally display the file path
                    ->label('File')
                    ->url(fn ($record) => Storage::url($record->file_path)), // Show URL of the file if needed
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultGroup('unit');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCurricula::route('/'),
            'create' => Pages\CreateCurriculum::route('/create'),
            'edit' => Pages\EditCurriculum::route('/{record}/edit'),
        ];
    }
}