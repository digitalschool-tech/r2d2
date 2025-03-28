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
use Illuminate\Support\Facades\Storage;

class H5PResource extends Resource
{
    protected static ?string $model = H5P::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'H5P Content';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Curriculum Information')
                    ->schema([
                        Forms\Components\TextInput::make('curriculum.title')
                            ->label('Lesson Title')
                            ->disabled()
                            ->afterStateHydrated(function ($component, $state, $record) {
                                $component->state($record->curriculum?->title);
                            }),
                        Forms\Components\TextInput::make('curriculum.unit')
                            ->label('Unit')
                            ->disabled()
                            ->afterStateHydrated(function ($component, $state, $record) {
                                $component->state($record->curriculum?->unit);
                            }),
                        Forms\Components\TextInput::make('curriculum.lesson')
                            ->label('Lesson Number')
                            ->disabled()
                            ->afterStateHydrated(function ($component, $state, $record) {
                                $component->state($record->curriculum?->lesson);
                            }),
                    ])->columns(3),

                Forms\Components\Section::make('Curriculum Content')
                    ->schema([
                        Forms\Components\Textarea::make('curriculum.content')
                            ->label('Content')
                            ->disabled()
                            ->afterStateHydrated(function ($component, $state, $record) {
                                $component->state($record->curriculum?->content);
                            })
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('curriculum.prompt')
                            ->label('Curriculum Prompt')
                            ->disabled()
                            ->afterStateHydrated(function ($component, $state, $record) {
                                $component->state($record->curriculum?->prompt);
                            })
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('curriculum.file_path')
                            ->label('File Path')
                            ->disabled()
                            ->afterStateHydrated(function ($component, $state, $record) {
                                $component->state($record->curriculum?->file_path);
                            })
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('open')
                                    ->icon('heroicon-m-arrow-top-right-on-square')
                                    ->url(fn ($record) => $record->curriculum?->file_path ? Storage::url($record->curriculum->file_path) : null, true)
                                    ->visible(fn ($record) => $record->curriculum?->file_path !== null)
                            ),
                        Forms\Components\Textarea::make('curriculum.pdf_content')
                            ->label('PDF Content')
                            ->disabled()
                            ->afterStateHydrated(function ($component, $state, $record) {
                                $component->state($record->curriculum?->pdf_content);
                            })
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('H5P Feedback')
                    ->schema([
                        Forms\Components\TextInput::make('rating')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(5)
                            ->label('Rating (1-5)'),
                        Forms\Components\Textarea::make('feedback')
                            ->maxLength(65535),
                    ])->columns(2),

                Forms\Components\Section::make('H5P Details')
                    ->schema([
                        Forms\Components\View::make('filament.resources.h5p.gpt-response')
                            ->label('Quiz Questions')
                            ->afterStateHydrated(function ($component, $state, $record) {
                                if (!$record->gpt_response) return;
                                
                                try {
                                    $questions = json_decode($record->gpt_response, true);
                                    $component->state($questions);
                                } catch (\Exception $e) {
                                    // Handle invalid JSON
                                    $component->state(null);
                                }
                            })
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('view_url')
                            ->label('View URL')
                            ->disabled()
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('open')
                                    ->icon('heroicon-m-arrow-top-right-on-square')
                                    ->url(fn ($record) => $record->view_url, true)
                                    ->visible(fn ($record) => $record->view_url !== null)
                            ),
                    ])
                    ->columns(2),
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
            'edit' => Pages\EditH5P::route('/{record}/edit'),
            'view' => Pages\ViewH5P::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['curriculum']);
    }
}