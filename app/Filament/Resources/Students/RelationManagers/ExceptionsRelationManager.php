<?php

namespace App\Filament\Resources\Students\RelationManagers;

use App\Filament\Resources\Students\StudentResource;
use App\Models\Semester;
use Filament\Actions\CreateAction;
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

//    protected static ?string $relatedResource = StudentResource::class;
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('semester_id')
                    ->label('Semester')
                    ->options(Semester::where('status', 1)->pluck('name', 'id'))
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set) {
                        // When semester changes, auto-set the date range
                        $semester = Semester::find($state);
                        if ($semester) {
                            $set('from_date', $semester->start_date);
                            $set('to_date', $semester->end_date);
                        }
                    }),
                DatePicker::make('from_date')
                    ->native(false)
                    ->placeholder('DD-MM-YYYY')
                    ->displayFormat('d/m/Y')
                    ->rules([
                        fn (Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                            $semesterId = $get('semester_id');
                            if (!$semesterId) return;

                            $semester = Semester::find($semesterId);
                            if ($semester && $value < $semester->start_date) {
                                $fail("The start date must be after or equal to the semester start date ({$semester->start_date->format('d/m/Y')}).");
                            }
                        }
                    ]),
                DatePicker::make('to_date')
                    ->native(false)
                    ->placeholder('DD-MM-YYYY')
                    ->displayFormat('d/m/Y')
                    ->required()
                    ->rules([
                        function (Get $get): \Closure {
                            return function (string $attribute, $value, \Closure $fail) use ($get) {
                                $semesterId = $get('semester_id');
                                $fromDate = $get('from_date');

                                if (!$semesterId) return;

                                $semester = Semester::find($semesterId);

                                // Convert both to Carbon dates with time set to startOfDay
                                $valueDate = Carbon::parse($value)->startOfDay();
                                $semesterEnd = Carbon::parse($semester->end_date)->startOfDay();
                                $fromDateDate = $fromDate ? Carbon::parse($fromDate)->startOfDay() : null;

                                // Validate against semester end date
                                if ($semester && $valueDate->gt($semesterEnd)) {
                                    $fail("The end date must be before or equal to the semester end date ({$semesterEnd->format('d/m/Y')}).");
                                }

                                // Validate against from date
                                if ($fromDateDate && $valueDate->lt($fromDateDate)) {
                                    $fail("The end date must be after the start date.");
                                }
                            };
                        }
                    ]),

                Textarea::make('reason'),

            ]);
    }

    /**
     * @throws \Exception
     */
    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('semester_id')
            ->columns([
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
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
