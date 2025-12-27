<?php

namespace App\Filament\Resources\StudentExceptions\Schemas;

use App\Models\Semester;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class StudentExceptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('semester_id')
                    ->label('Semester')
                    ->options(Semester::where('status', 1)->pluck('name', 'id'))
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, $set) {
                        $semester = Semester::find($state);
                        if ($semester) {
                            $set('from_date', $semester->start_date);
                            $set('to_date', $semester->end_date);
                        }
                    }),
                TextInput::make('student_id')
                    ->label('Student ID')
                    ->numeric()
                    ->required()
                    ->rules(['exists:students,student_id'])
                    ->validationMessages([
                        'exists' => 'The selected student does not exist.',
                    ]),
                DatePicker::make('from_date')
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->required()
                    ->rules([
                        function (Get $get) {
                            return function (string $attribute, $value, Closure $fail) use ($get) {
                                $semesterId = $get('semester_id');
                                $toDate = $get('to_date');

                                if ($semesterId && $value) {
                                    $semester = Semester::find($semesterId);
                                    if ($semester) {
                                        $semesterStart = \Carbon\Carbon::parse($semester->start_date)->format('Y-m-d');
                                        $fromDate = \Carbon\Carbon::parse($value)->format('Y-m-d');

                                        if ($fromDate < $semesterStart) {
                                            $formattedStartDate = \Carbon\Carbon::parse($semester->start_date)->format('d/m/Y');
                                            $fail("From date cannot be before the semester start date ({$formattedStartDate}).");
                                        }
                                    }
                                }

                                if ($toDate && $value) {
                                    $toDateFormatted = \Carbon\Carbon::parse($toDate)->format('Y-m-d');
                                    $fromDateFormatted = \Carbon\Carbon::parse($value)->format('Y-m-d');

                                    if ($fromDateFormatted > $toDateFormatted) {
                                        $fail("From date cannot be after to date.");
                                    }
                                }
                            };
                        },
                    ]),
                DatePicker::make('to_date')
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->required()
                    ->rules([
                        function (Get $get) {
                            return function (string $attribute, $value, Closure $fail) use ($get) {
                                $semesterId = $get('semester_id');
                                $fromDate = $get('from_date');

                                if ($semesterId && $value) {
                                    $semester = Semester::find($semesterId);
                                    if ($semester) {
                                        $semesterEnd = \Carbon\Carbon::parse($semester->end_date)->format('Y-m-d');
                                        $toDate = \Carbon\Carbon::parse($value)->format('Y-m-d');

                                        if ($toDate > $semesterEnd) {
                                            $formattedEndDate = \Carbon\Carbon::parse($semester->end_date)->format('d/m/Y');
                                            $fail("To date cannot be after the semester end date ({$formattedEndDate}).");
                                        }
                                    }
                                }

                                if ($fromDate && $value) {
                                    $fromDateFormatted = \Carbon\Carbon::parse($fromDate)->format('Y-m-d');
                                    $toDateFormatted = \Carbon\Carbon::parse($value)->format('Y-m-d');

                                    if ($toDateFormatted < $fromDateFormatted) {
                                        $fail("To date cannot be before from date.");
                                    }
                                }
                            };
                        },
                    ]),
                Textarea::make('reason'),
            ]);
    }
}
