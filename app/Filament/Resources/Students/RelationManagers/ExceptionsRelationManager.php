<?php

namespace App\Filament\Resources\Students\RelationManagers;

use App\Filament\Resources\Students\StudentResource;
use App\Models\Semester;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
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
                    ->required()
                    ->native(false)                // use JS picker (Flatpickr)
                    ->displayFormat('d/m/Y')       // human-friendly display
                    ->placeholder('DD/MM/YYYY')
                    ->suffixIcon('heroicon-o-calendar')
                    ->closeOnDateSelection(true)
                    ->beforeOrEqual('to_date')
                    ->rule(function (callable $get) {

                        if (!$get('semester_id') || !$get('from_date')) {
                            return null;
                        }

                        $semester = Semester::find($get('semester_id'));

                        return function (string $attribute, $value, $fail) use ($semester) {
                            if ($value < $semester->start_date || $value > $semester->end_date) {
                                $start = \Carbon\Carbon::parse($semester->start_date)->format('d/m/Y');
                                $end   = \Carbon\Carbon::parse($semester->end_date)->format('d/m/Y');
                                $fail("The start date must be within {$start} and {$end}.");
                            }
                        };
                    }),
                DatePicker::make('to_date')
                    ->native(false)
                    ->displayFormat('d/m/Y')       // human-friendly display
                    ->placeholder('DD/MM/YYYY')
                    ->suffixIcon('heroicon-o-calendar')
                    ->required()
                    ->afterOrEqual('from_date')
                    ->closeOnDateSelection(true)
                    ->rule(function (callable $get) {

                        if (!$get('semester_id') || !$get('to_date')) {
                            return null;
                        }

                        $semester = Semester::find($get('semester_id'));

                        return function (string $attribute, $value, $fail) use ($semester) {
                            if ($value < $semester->start_date || $value > $semester->end_date) {
                                $start = \Carbon\Carbon::parse($semester->start_date)->format('d/m/Y');
                                $end   = \Carbon\Carbon::parse($semester->end_date)->format('d/m/Y');
                                if ($value < $semester->start_date || $value > $semester->end_date) {
                                    $fail("The end date must be within {$start} and {$end}.");
                                }                            }
                        };
                    }),

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
                EditAction::make('edit'),
                DeleteAction::make('delete')
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
