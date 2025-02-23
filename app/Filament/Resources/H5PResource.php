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
use Filament\Tables\Columns\TextColumn;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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
        // Get all files from the h5p/generated directory
        $files = collect(Storage::disk('public')->files('h5p/generated'))
            ->map(function($file) {
                return [
                    'filename' => basename($file),
                    'path' => $file,
                    'created_at' => Carbon::createFromTimestamp(Storage::disk('public')->lastModified($file)),
                    'size' => Storage::disk('public')->size($file)
                ];
            })
            ->sortByDesc('created_at')
            ->values();

        return $table
            ->query(
                // Convert the files collection to a query builder
                \Illuminate\Database\Query\Builder::fromQuery(
                    \Illuminate\Support\Facades\DB::table('dummy')->whereRaw('1=0')
                        ->unionAll(
                            \Illuminate\Support\Facades\DB::table('dummy')
                                ->whereRaw('1=0')
                                ->addSelect(\Illuminate\Support\Facades\DB::raw("'" . implode("' as filename, '", $files->pluck('filename')->toArray()) . "' as filename"))
                        )
                )
            )
            ->columns([
                TextColumn::make('filename')
                    ->label('File')
                    ->formatStateUsing(fn ($record) => "<a href='/storage/h5p/generated/{$record->filename}' target='_blank'>{$record->filename}</a>")
                    ->html()
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('created_at')
                    ->label('Created')
                    ->formatStateUsing(fn ($record) => Carbon::parse($files->firstWhere('filename', $record->filename)['created_at'])->format('M j, Y H:i:s'))
                    ->sortable(),
                
                TextColumn::make('size')
                    ->label('Size')
                    ->formatStateUsing(fn ($record) => self::formatBytes($files->firstWhere('filename', $record->filename)['size']))
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    // Helper function to format bytes
    private static function formatBytes($bytes, $precision = 2) 
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
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
