<?php

namespace App\Filament\Resources\StudentResource\RelationManagers;

use App\Models\Semester;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExceptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'exceptions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([

                Select::make('semester_id')
                    ->label('Semester')
                    ->options(Semester::where('status', 1)->pluck('name', 'id'))
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
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

                                // Validate against semester end date
                                if ($semester && $value > $semester->end_date) {
                                    $fail("The end date must be before or equal to the semester end date ({$semester->end_date->format('d/m/Y')}).");
                                }

                                // Validate against from date
                                if ($fromDate && $value < $fromDate) {
                                    $fail("The end date must be after the start date.");
                                }
                            };
                        }
                    ]),
                Forms\Components\Textarea::make('reason'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('semester_id')
            ->columns([
                Tables\Columns\TextColumn::make('student.name'),
//                Tables\Columns\TextColumn::make('semester.name')
//                    ->label('Semester'),
//                Tables\Columns\TextColumn::make('semester.year')
//                    ->label('Year'),
//                Tables\Columns\TextColumn::make('from_date')
//                    ->date(),
//                Tables\Columns\TextColumn::make('to_date')
//                    ->date(),
//                Tables\Columns\TextColumn::make('reason')
//                    ->limit(50),
//                Tables\Columns\TextColumn::make('created_at')
//                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
