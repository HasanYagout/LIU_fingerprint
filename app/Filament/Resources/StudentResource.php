<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Filament\Resources\StudentResource\RelationManagers;
use App\Helpers\Helpers;
use App\Imports\StudentsImport;
use App\Models\Semester;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Students';
    protected static ?string $navigationGroup = 'Students';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                TextInput::make('id')
                    ->label('Student ID')
                    ->required()
                    ->hiddenOn('edit')
                    ->unique(ignoreRecord: true)
                    ->rules([
                        'digits:8',  // Must be exactly 8 digits
                    ]),
                TextInput::make('name')
                    ->label('Full Name')
                    ->hiddenOn('edit')
                    ->required(),
                TextInput::make('major')
                    ->label('Major')
                    ->hiddenOn('edit')
                    ->required(),
                Select::make('semester_id')
                    ->hiddenOn('edit')
                    ->options(
                        Semester::where('status', 1)
                            ->get()
                            ->mapWithKeys(function ($semester) {
                                return [$semester->id => $semester->name . ' - ' . $semester->year];
                            })
                    )
                    ->preload()
                    ->required(),
                TextInput::make('percentage')
                    ->hiddenOn('edit')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->required(),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->headerActions([
                Action::make('create-exception')
                    ->label('Create Exception')
                    ->color('secondary')
                    ->form([
                        Select::make('student_id')
                            ->label('Student')
                            ->options(Student::all()->pluck('name', 'student_id')->toArray())
                            ->searchable()
                            ->required(),
                        Select::make('semester_id')
                            ->label('Semester')
                            ->options(Semester::all()->pluck('name', 'id')->toArray())
                            ->searchable()
                            ->required(),
                        Forms\Components\DatePicker::make('from_date')
                            ->displayFormat('d-m-Y') // Shows as DD-MM-YYYY
                            ->required(),
                        Forms\Components\DatePicker::make('to_date')
                            ->displayFormat('d-m-Y') // Shows as DD-MM-YYYY
                            ->required(),
                        Forms\Components\Textarea::make('reason')
                            ->label('Reason for Exception'),
                    ])
                    ->action(function (array $data): void {
                        \App\Models\StudentException::create($data);


                        Notification::make()
                            ->title('Exception created successfully')
                            ->success()
                            ->send();
                    }),
                Action::make('import')
                    ->label('Import Students')
                    ->color('primary')
                    ->form([
                        Select::make('semester_id')
                            ->label('Semester')
                            ->options(Semester::where('status',1)->pluck('name', 'id')->toArray())
                            ->searchable()
                            ->required(),
                        FileUpload::make('file')
                            ->label('Excel / CSV File')
                            ->required()
                            ->disk('local')
                            ->directory('imports')
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'text/csv',
                            ]),
                    ])
                    ->action(function (array $data): void {
                        $semesterId = $data['semester_id'];
                        $relativePath = $data['file'];
                        $fullPath = Storage::disk('local')->path($relativePath);

                        Excel::import(new StudentsImport($semesterId), $fullPath);

                        Notification::make()
                            ->title('Students imported successfully')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->size('lg'),
            ])
            ->query(
                Student::query()
                    ->with('semesters')
                    ->join('semester_student', 'students.student_id', '=', 'semester_student.student_id')
                    ->join('semesters', 'semester_student.semester_id', '=', 'semesters.id')
                    ->where('semesters.status', 1) // Filter by active status directly
                    ->select([
                        'students.*',
                        'semesters.name as semester_name',
                        'semesters.year as year',
                        'semesters.id as semester_id',
                        'semester_student.percentage as pivot_percentage'
                    ])
                    ->distinct('students.id')
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

                TextColumn::make('year')
                    ->searchable(query: function (Builder $query, string $search) {
                        $query->where('semesters.year', 'like', "%{$search}%");
                    })
                    ->sortable(),


                TextColumn::make('major')
                    ->label('Major')
                    ->searchable(query: function (Builder $query, string $search) {
                        $query->where('students.major', 'like', "%{$search}%");
                    })
                    ->sortable(),

                TextColumn::make('paid_pct')
                    ->label('Paid %')
                    ->getStateUsing(fn($record) => Helpers::getPaymentStatus(
                        $record->student_id,
                        $record->semester_id
                    )['percentage'])
                    ->color(fn($record) => Helpers::getPaymentStatus(
                        $record->student_id,
                        $record->semester_id
                    )['color'])
                    ->sortable()
                    ->description(fn($record) => 'Required: ' .
                        Helpers::getPaymentStatus(
                            $record->student_id,
                            $record->semester_id
                        )['required'] . '%')
            ])
            ->filters([
                SelectFilter::make('payment_status')
                    ->label('Payment Status')
                    ->options([
                        'paid' => 'Paid (Met Requirements)',
                        'not_paid' => 'Not Paid (Below Requirements)',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) {
                            return;
                        }

                        // Only proceed for "not paid" filter
                        if ($data['value'] === 'not_paid') {
                            $query->where(function($q) {
                                $q->whereHas('semesters', function($semesterQuery) {
                                    $semesterQuery->where('status', 1) // Active semesters only
                                    ->where(function($subQuery) {
                                        // Get all students in active semesters
                                        $students = Student::with(['semesters' => function($q) {
                                            $q->where('status', 1);
                                        }])->get();

                                        // Filter students using your existing function
                                        $unpaidStudentIds = [];
                                        foreach ($students as $student) {
                                            foreach ($student->semesters as $semester) {
                                                $paymentStatus = Helpers::getPaymentStatus(
                                                    $student->student_id,
                                                    $semester->id
                                                );
                                                if ($paymentStatus['percentage'] < $paymentStatus['required']) {
                                                    $unpaidStudentIds[] = $student->id;
                                                    break; // Student is unpaid in at least one semester
                                                }
                                            }
                                        }

                                        // Apply filter to only show unpaid students
                                        $subQuery->whereIn('students.id', $unpaidStudentIds);
                                    });
                                });
                            });
                        } else {
                            // For "paid" filter - same logic but with >= comparison
                            $query->where(function($q) {
                                $q->whereHas('semesters', function($semesterQuery) {
                                    $semesterQuery->where('status', 1)
                                        ->where(function($subQuery) {
                                            $students = Student::with(['semesters' => function($q) {
                                                $q->where('status', 1);
                                            }])->get();

                                            $paidStudentIds = [];
                                            foreach ($students as $student) {
                                                $allPaid = true;
                                                foreach ($student->semesters as $semester) {
                                                    $paymentStatus = Helpers::getPaymentStatus(
                                                        $student->student_id,
                                                        $semester->id
                                                    );
                                                    if ($paymentStatus['percentage'] < $paymentStatus['required']) {
                                                        $allPaid = false;
                                                        break;
                                                    }
                                                }
                                                if ($allPaid) {
                                                    $paidStudentIds[] = $student->id;
                                                }
                                            }

                                            $subQuery->whereIn('students.id', $paidStudentIds);
                                        });
                                });
                            });
                        }
                    }),
//                SelectFilter::make('semester')
//                    ->options(function () {
//                        return Semester::query()
//                            // only those semesters referenced in semester_student:
//                            ->join('semester_student', 'semesters.id', '=', 'semester_student.semester_id')
//                            // pick only the name, once each
//                            ->distinct('semesters.name')
//                            ->pluck('semesters.name', 'semesters.name')
//                            ->toArray();
//                    })
//                    ->query(function (Builder $query, array $data) {
//                        if (empty($data['value'])) {
//                            return;
//                        }
//
//                        // filter by name
//                        $query->where('semesters.name', $data['value']);
//                    })
//                    ->searchable()
//                    ->preload(),

//        SelectFilter::make('year')
//                    ->label('Year')
//                    ->options(function () {
//                        return Semester::query()
//                            ->select('year')
//                            ->distinct()
//                            ->orderBy('year', 'desc')
//                            ->pluck('year', 'year');
//                    })
//                    ->query(function (Builder $query, $data) {
//                        if (!$data['value']) return;
//
//                        $query->where('semesters.year', $data['value']);
//                    }),

                SelectFilter::make('major')
                    ->label('Major')
                    ->options(function () {
                        return Student::query()
                            ->select('major')
                            ->distinct()
                            ->orderBy('major')
                            ->pluck('major', 'major');
                    })
                    ->query(function (Builder $query, $data) {
                        if (!$data['value']) return;

                        $query->where('students.major', $data['value']);
                    })
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SemestersRelationManager::class,
            RelationManagers\ExceptionsRelationManager::class,

        ];
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }
}
