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
use Illuminate\Support\Facades\Storage;
use Filament\Tables\Columns\TextColumn;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class H5PResource extends Resource
{
    protected static ?string $navigationLabel = 'H5P';
    protected static ?string $navigationGroup = 'Content';
    protected static ?string $model = H5P::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('curriculum_id')
                    ->label('Curriculum')
                    ->options(fn() => Curriculum::all()->pluck('title', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Textarea::make('prompt')
                    ->label('Prompt')
                    ->disabled()
                    ->rows(10)
                    ->formatStateUsing(function ($state) {
                        return $state ? json_encode(json_decode($state), JSON_PRETTY_PRINT) : '';
                    })
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('filename')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        // Retrieve all files from the h5p/generated directory
        $files = collect(Storage::disk('public')->files('h5p/generated'))
            ->map(function ($file) {
                return [
                    'filename'   => basename($file),
                    'path'       => $file,
                    'created_at' => Carbon::createFromTimestamp(Storage::disk('public')->lastModified($file)),
                    'size'       => Storage::disk('public')->size($file),
                ];
            })
            ->sortByDesc('created_at')
            ->values();

        // Build a union query that returns filename, created_at, and size.
        if ($files->isEmpty()) {
            $query = DB::query()->fromSub(DB::table('dummy')->whereRaw('1=0'), 'files');
        } else {
            $selects = $files->map(function ($file) {
                return "SELECT '" . $file['filename'] . "' as filename, '" 
                    . $file['created_at']->format('Y-m-d H:i:s') . "' as created_at, " 
                    . $file['size'] . " as size";
            })->implode(" UNION ALL ");
            $query = DB::query()->fromSub(DB::raw($selects), 'files');
        }

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('filename')
                    ->label('File')
                    ->formatStateUsing(fn ($record) => "<a href='/storage/h5p/generated/{$record->filename}' target='_blank'>{$record->filename}</a>")
                    ->html()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->formatStateUsing(fn ($record) => Carbon::parse($record->created_at)->format('M j, Y H:i:s'))
                    ->sortable(),
                TextColumn::make('size')
                    ->label('Size')
                    ->formatStateUsing(fn ($record) => self::formatBytes($record->size))
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    // Helper function to format bytes into human-readable sizes
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
            // Define relation managers here if needed.
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListH5PS::route('/'),
            'create' => Pages\CreateH5P::route('/create'),
            'edit'   => Pages\EditH5P::route('/{record}/edit'),
        ];
    }
}
