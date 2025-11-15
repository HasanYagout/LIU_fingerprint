<?php

namespace App\Filament\Resources\Students\RelationManagers;

use App\Filament\Resources\Students\StudentResource;
use App\Models\Semester;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class ExceptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'exceptions';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('semester_id')
                    ->label('Semester')
                    ->options(Semester::where('status', 1)->pluck('name', 'id'))
                    ->required()
                    ->live(),
                DatePicker::make('from_date')
                    ->native(false)                // use JS picker (Flatpickr)
                    ->displayFormat('d/m/Y')       // human-friendly display
                    ->placeholder('DD/MM/YYYY')
                    ->suffixIcon('heroicon-o-calendar')
                    ->closeOnDateSelection(true)
                    ->beforeOrEqual('to_date'),
                DatePicker::make('to_date')
                    ->native(false)
                    ->displayFormat('d/m/Y')       // human-friendly display
                    ->placeholder('DD/MM/YYYY')
                    ->suffixIcon('heroicon-o-calendar')
                    ->required()
                    ->closeOnDateSelection(true)
                    ->afterOrEqual('from_date'),

                Textarea::make('reason'),

            ]);
    }

    /**
     * @throws \Exception
     */
    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('student.name')
            ->columns([
                TextColumn::make('student.student_id')
                ->label('Student ID'),
                TextColumn::make('student.name')
                ->label('Name'),
                TextColumn::make('user.name')
                    ->label('Created By')
                    ->badge(),
                TextColumn::make('semester.name')
                    ->label('Semester'),
                TextColumn::make('from_date')
                    ->date('Y-m-d'),
                TextColumn::make('to_date')
                    ->date('Y-m-d'),
                TextColumn::make('reason')
                    ->limit(50),
                TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->recordActions([
                EditAction::make('edit')
            ])
            ->headerActions([
                CreateAction::make()
                ->mutateDataUsing(function (array $data) {
                    $data['created_by'] = auth()->id();

                    return $data;
                }),
            ]);
    }
}
