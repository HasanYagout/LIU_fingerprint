<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Filament\Resources\StudentResource\RelationManagers;
use App\Imports\StudentsImport;
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
                        'paid'    => 'Paid',
                        'unpaid'  => 'Unpaid',
                        'partial' => 'Partial',
                    ])
                    ->required(),
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
                        $relativePath = $data['file'];
                        $fullPath = Storage::disk('local')->path($relativePath);

                        // Import via Laravel Excel
                        Excel::import(new StudentsImport, $fullPath);

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
                TextColumn::make('major')
                    ->label('Major')
                    ->sortable(),
                TextColumn::make('percentage')

                    ->badge()
                    ->sortable(),
            ])
            // ——————————————— Table Filters ———————————————
            ->filters([
                SelectFilter::make('payment_status')
                    ->label('Payment Status')
                    ->options([
                        'paid'    => 'Paid',
                        'unpaid'  => 'Unpaid',
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
            'index'   => Pages\ListStudents::route('/'),
            'create'  => Pages\CreateStudent::route('/create'),
            'edit'    => Pages\EditStudent::route('/{record}/edit'),
        ];
    }
}
