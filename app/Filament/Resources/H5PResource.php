<?php

namespace App\Filament\Resources;

use App\Filament\Resources\H5PResource\Pages;
use App\Models\H5P;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class H5PResource extends Resource
{
    protected static ?string $model = H5P::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'H5P Content';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('rating')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(5)
                    ->label('Rating (1-5)'),
                Forms\Components\Textarea::make('feedback')
                    ->maxLength(65535),
                Forms\Components\Textarea::make('gpt_response')
                    ->label('GPT Response')
                    ->disabled()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('curriculum.title')
                    ->label('Lesson Title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('unit')
                    ->label('Unit')
                    ->formatStateUsing(fn ($record) => $record->curriculum?->unit ?? '-')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('lesson')
                    ->label('Lesson')
                    ->formatStateUsing(fn ($record) => $record->curriculum?->lesson ?? '-')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('rating')
                    ->sortable()
                    ->label('Rating (1-5)')
                    ->formatStateUsing(fn ($state) => $state ? "★ {$state}/5" : '-'),
                TextColumn::make('feedback')
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('view_url')
                    ->label('View Link')
                    ->url(fn ($record) => $record->view_url)
                    ->openUrlInNewTab(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListH5Ps::route('/'),
            'create' => Pages\CreateH5P::route('/create'),
            'edit' => Pages\EditH5P::route('/{record}/edit'),
            'view' => Pages\ViewH5P::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['curriculum']);
    }
} 