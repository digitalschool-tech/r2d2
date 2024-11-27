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
                Forms\Components\TextInput::make('filename')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('prompt'),
                Tables\Columns\TextColumn::make('filename'),
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
