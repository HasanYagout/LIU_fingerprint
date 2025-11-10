<?php

namespace App\Filament\Resources\Students\Tables;

use App\Helpers\Helpers;
use App\Models\Student;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StudentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(
                Student::query()
                    ->with('semesters')
                    ->join('semester_student', 'students.student_id', '=', 'semester_student.student_id')
                    ->join('semesters', 'semester_student.semester_id', '=', 'semesters.id')
                    ->where('semesters.status', 1) // Filter by active status directly
                    ->select([
                        'students.*',
                        'semesters.name as semester_name',
                        'semesters.id as semester_id',
                        'semester_student.percentage as pivot_percentage',

                    ])
                    ->distinct('students.id'),

            )
            ->columns([
                TextColumn::make('student_id')
                    ->label('Student ID')
                    ->searchable(query: function (Builder $query, string $search) {
                        $query->where('students.student_id', 'like', "%{$search}%");
                    })
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Full Name')
                    ->searchable(query: function (Builder $query, string $search) {
                        $query->where('students.name', 'like', "%{$search}%");
                    })
                    ->sortable(),

                TextColumn::make('semester_name')
                    ->label('Semester')
                    ->searchable(query: function (Builder $query, string $search) {
                        $query->where('semesters.name', 'like', "%{$search}%");
                    })
                    ->sortable(),




                TextColumn::make('major')
                    ->label('Major')
                    ->searchable(query: function (Builder $query, string $search) {
                        $query->where('students.major', 'like', "%{$search}%");
                    })
                    ->sortable(),

                TextColumn::make('pivot_percentage')
                    ->label('Paid %')
                    ->getStateUsing(fn($record) => Helpers::getPaymentStatus(
                        $record->student_id,
                        $record->semester_id
                    )['percentage'])
                    ->sortable('pivot_percentage')
                    ->color(fn($record) => Helpers::getPaymentStatus(
                        $record->student_id,
                        $record->semester_id
                    )['color'])
                    ->description(fn($record) => 'Required: ' .
                        Helpers::getPaymentStatus(
                            $record->student_id,
                            $record->semester_id
                        )['required'] . '%')
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
