<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Filament\Resources\StudentResource\RelationManagers;
use App\Helpers\Helpers;
use App\Imports\StudentsImport;
use Illuminate\Database\Eloquent\Builder;
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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Students';
    protected static ?string $navigationGroup = 'Students';
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->join('semester_student', 'semester_student.student_id', '=', 'students.student_id')
            ->join('semesters',         'semesters.id',           '=', 'semester_student.semester_id')
            ->select([
                'students.*',
                'semester_student.percentage as paid_percentage',
                'semester_student.semester_id as pivot_semester_id',
                'semesters.name as semester_name',
            ]);
    }
    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                TextInput::make('student_id')
                    ->label('Student ID')
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('name')
                    ->label('Full Name')
                    ->required(),
                TextInput::make('major')
                    ->label('Major')
                    ->required(),
                TextInput::make('level')
                    ->label('Level')
                    ->required(),
                Select::make('payment_status')
                    ->label('Payment Status')
                    ->options([
                        'paid' => 'Paid',
                        'unpaid' => 'Unpaid',
                        'partial' => 'Partial',
                    ])
                    ->required(),
                TextColumn::make('percentage')
                    ->label('Paid %')
                    // 1) Resolve the “current” percentage from pivot
                    ->getStateUsing(function (Student $record) {
                        $today = Carbon::now()->startOfDay();
                        $sem = Semester::where('start_date', '<=', $today)
                            ->where('end_date', '>=', $today)
                            ->first();
                        if (!$sem) {
                            return 'N/A';
                        }
                        $pivot = $record->semesters()
                            ->where('semester_id', $sem->id)
                            ->first()?->pivot;

                        return $pivot
                            ? (int)$pivot->percentage
                            : 0;
                    })
                    // 2) Color‐code green when ≥ threshold, red otherwise
                    ->colors([
                        'success' => static function ($state) {
                            $today = Carbon::now()->startOfDay();
                            $sem = Semester::where('start_date', '<=', $today)
                                ->where('end_date', '>=', $today)
                                ->first();
                            if (!$sem) {
                                return false;
                            }
                            $phase = $today->lt($sem->midterm_date) ? 'start' : 'midterm';
                            $required = config("payment_thresholds.{$phase}", 0);

                            return $state >= $required;
                        },
                        'danger' => static function ($state) {
                            $today = Carbon::now()->startOfDay();
                            $sem = Semester::where('start_date', '<=', $today)
                                ->where('end_date', '>=', $today)
                                ->first();
                            if (!$sem) {
                                return false;
                            }
                            $phase = $today->lt($sem->midterm_date) ? 'start' : 'midterm';
                            $required = config("payment_thresholds.{$phase}", 0);

                            return $state < $required;
                        },
                    ])
                    ->sortable(),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            // ——————————————— Header Action: Import Students ———————————————
            ->headerActions([
                Action::make('import')
                    ->label('Import Students')
                    ->color('primary')
                    ->form([
                        Select::make('semester_id')
                            ->label('Semester')
                            ->options(Semester::all()->pluck('name', 'id')->toArray())
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
                        // $data['file'] is something like "imports/abcd1234.xlsx"
                        $semesterId = $data['semester_id'];
                        $relativePath = $data['file'];
                        $fullPath = Storage::disk('local')->path($relativePath);

                        // Import via Laravel Excel
                        Excel::import(new StudentsImport($semesterId), $fullPath);

                        // Show a Filament notification
                        Notification::make()
                            ->title('Students imported successfully')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation() // optional: ask “Are you sure?” before uploading
                    ->size('lg'),
            ])
            // ——————————————— Table Columns ———————————————
            ->columns([
                TextColumn::make('student_id')
                    ->label('Student ID')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Full Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('semester_name')
                    ->label('Semester')
                    ->searchable(['semesters.name'])
                    ->sortable(['semesters.name']),
                TextColumn::make('major')
                    ->label('Major')
                    ->sortable(),

                TextColumn::make('paid_pct')
                    ->label('Paid %')
                    // display the actual paid percentage
                    ->getStateUsing(fn($record) => Helpers::getPaymentStatus(
                        $record->student_id,
                        $record->pivot_semester_id // or wherever you store semester_id
                    )['percentage'])
                    // color by whether it meets the windowed threshold
                    ->colors([
                        'success' => fn($record): bool => Helpers::getPaymentStatus(
                                $record->student_id,
                                $record->pivot_semester_id
                            )['color'] === 'success',
                        'danger'  => fn($record): bool => Helpers::getPaymentStatus(
                                $record->student_id,
                                $record->pivot_semester_id
                            )['color'] === 'danger',
                    ])
                    ->sortable(['semester_student.percentage'])
                    ->description(fn($record) => 'Required: '
                        . Helpers::getPaymentStatus(
                            $record->student_id,
                            $record->pivot_semester_id
                        )['required'] . '%')


            ])
            // ——————————————— Table Filters ———————————————
            ->filters([
                SelectFilter::make('payment_status')
                    ->label('Payment Status')
                    ->options([
                        'paid' => 'Paid',
                        'unpaid' => 'Unpaid',
                        'partial' => 'Partial',
                    ]),
            ])
            // ——————————————— Row Actions ———————————————
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            // ——————————————— Bulk Actions ———————————————
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
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
