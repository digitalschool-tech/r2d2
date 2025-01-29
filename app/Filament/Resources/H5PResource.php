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
                Tables\Columns\TextColumn::make('filename'),
                Tables\Columns\TextColumn::make('curriculum.title')
                    ->label('Curriculum'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('uploadToMoodle')
                    ->label('Upload to Moodle')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->action(function ($record) {
                        try {
                            $filePath = storage_path('app/private/h5p/generated/' . $record->filename);
                            
                            if (!file_exists($filePath)) {
                                throw new \Exception('H5P file not found');
                            }

                            $response = MoodleController::uploadH5PDirectly(
                                $filePath,
                                24, // Hardcoded course ID
                                7,   // Hardcoded section ID
                                $record->prompt,
                            );

                            $responseData = $response->json();
                            
                            if ($response->failed()) {
                                Notification::make()
                                    ->title('Upload Failed')
                                    ->body('Failed to upload H5P to Moodle: ' . ($responseData['message'] ?? dd($response)))
                                    ->danger()
                                    ->send();
                                
                                return;
                            }
                            
                            Notification::make()
                                ->title('Upload Successful')
                                ->body('H5P content has been uploaded to Moodle')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Upload Failed')
                                ->body('An error occurred: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Upload to Moodle')
                    ->modalDescription('Are you sure you want to upload this H5P content to Moodle?')
                    ->modalSubmitActionLabel('Yes, upload it'),
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
