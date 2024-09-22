<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkflowResource\Pages;
use App\Models\Workflow;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\ViewField;

class WorkflowResource extends Resource
{
    protected static ?string $model = Workflow::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Name input
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->label('Workflow Name'),

                // Description input
                Forms\Components\Textarea::make('description')
                    ->label('Workflow Description'),

                // Route input
                Forms\Components\TextInput::make('route')
                    ->label('Workflow Route'),

                Forms\Components\Textarea::make('prompt')
                    ->label('Workflow Prompt')
                    ->columnSpan("full"),

                Forms\Components\TextInput::make('blocks')
                    ->label('Blocks JSON')
                    ->disabled(),

                // ViewField to render the Drawflow editor
                ViewField::make('blocks-view')
                    ->label('Workflow Editor (ignore for now)')
                    ->view('workflow-editor')
                    ->columnSpan('full')
                    ->disabled()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('description'),
                Tables\Columns\TextColumn::make('route')->sortable(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkflows::route('/'),
            'create' => Pages\CreateWorkflow::route('/create'),
            'edit' => Pages\EditWorkflow::route('/{record}/edit'),
        ];
    }
}