<?php

namespace App\Filament\Resources;

use App\Filament\Resources\H5PResource\Pages;
use App\Filament\Resources\H5PResource\RelationManagers;
use App\Models\H5P;
use App\Models\Curriculum;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Http\Controllers\MoodleController;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class H5PResource extends Resource
{
    protected static ?string $navigationLabel = 'H5P';  // Set the label
    protected static ?string $navigationGroup = 'Content'; // Optional group
    protected static ?string $model = H5P::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('curriculum_id')
                    ->label('Curriculum')
                    ->options(Curriculum::all()->pluck('title', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Textarea::make('prompt')
                    ->label('Prompt')
                    ->disabled()
                    ->rows(10)
                    ->formatStateUsing(function ($state) {
                        if ($state === null) {
                            return '';
                        }
                        return json_encode(json_decode($state), JSON_PRETTY_PRINT);
                    })
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('filename')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('filename')
                    ->label('File')
                    ->url(fn ($record) => Storage::disk('local')->url('h5p/generated/' . $record->filename))
                    ->openUrlInNewTab()
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('curriculum.title')
                    ->label('Curriculum')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y H:i:s')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListH5PS::route('/'),
            'create' => Pages\CreateH5P::route('/create'),
            'edit' => Pages\EditH5P::route('/{record}/edit'),
        ];
    }
}
