<?php

namespace App\Filament\Resources\Students\Tables;

use App\Helpers\Helpers;
use App\Imports\StudentsImport;
use App\Models\Semester;
use App\Models\Student;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;



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
                    ->select([
                        'students.student_id as student_id',
                        'students.name as student_name',
                        'students.major',
                        'students.created_at',
                        'students.updated_at',
                        'semesters.name as semester_name',
                        'semesters.id as semester_id',
                        'semester_student.percentage as pivot_percentage',
                    ])
                    ->groupBy('students.student_id', 'students.name', 'students.major', 'students.created_at', 'students.updated_at')

            )
            ->headerActions(
               [

               ]
            )
            ->columns([
                TextColumn::make('student_id')
                    ->label('Student ID')
                    ->searchable(query: function (Builder $query, string $search) {
                        $query->where('students.student_id', 'like', "%{$search}%");
                    })
                    ->sortable('student_id'),

                TextColumn::make('student_name')
                    ->label('Full Name')
                    ->searchable(query: function (Builder $query, string $search) {
                        $query->where('students.name', 'like', "%{$search}%");
                    })
                    ->sortable('student_name'), // fully qualified column
                TextColumn::make('major')
                    ->label('Major')
                    ->searchable(query: function (Builder $query, string $search) {
                        $query->where('students.major', 'like', "%{$search}%");
                    })
                    ->sortable(),

                TextColumn::make('pivot_percentage')
                    ->label('Paid %')
                    ->getStateUsing(fn($record) => Helpers::getStatus(
                        $record->student_id,
                        $record->semester_id
                    )['percentage'])
                    ->sortable('pivot_percentage')
                    ->color(fn($record) => Helpers::getStatus(
                        $record->student_id,
                        $record->semester_id
                    )['status'])
                    ->description(fn($record) => 'Required: ' .
                        Helpers::getStatus(
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

            ])->defaultPaginationPageOption(50);
    }
}
