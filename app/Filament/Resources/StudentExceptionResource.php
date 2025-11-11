<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentExceptionResource\Pages;
use App\Filament\Resources\StudentExceptionResource\RelationManagers;
use App\Models\Semester;
use App\Models\StudentException;
use Closure;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StudentExceptionResource extends Resource
{
    protected static ?string $model = StudentException::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('semester_id')
                    ->label('Semester')
                    ->options(Semester::where('status', 1)->pluck('name', 'id'))
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        $semester = Semester::find($state);
                        if ($semester) {
                            $set('from_date', $semester->start_date);
                            $set('to_date', $semester->end_date);
                        }
                    }),
                Forms\Components\TextInput::make('student_id')
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                ->searchable(),
                Tables\Columns\TextColumn::make('semester.name')
                    ->label('Semester'),
                Tables\Columns\TextColumn::make('user.name')
                    ->badge()
                    ->label('Created By'),
                Tables\Columns\TextColumn::make('from_date')
                    ->date(),
                Tables\Columns\TextColumn::make('to_date')
                    ->date(),
                Tables\Columns\TextColumn::make('reason')
                    ->limit(50),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->filters([
                Tables\Filters\Filter::make('from_date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from_date_from')
                            ->label('From Date From'),
                        Forms\Components\DatePicker::make('from_date_to')
                            ->label('From Date To'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from_date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('from_date', '>=', $date),
                            )
                            ->when(
                                $data['from_date_to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('from_date', '<=', $date),
                            );
                    }),
                Tables\Filters\SelectFilter::make('user')
                    ->label('Created By')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListStudentExceptions::route('/'),
            'create' => Pages\CreateStudentException::route('/create'),
            'edit' => Pages\EditStudentException::route('/{record}/edit'),
        ];
    }
}
