<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use App\Imports\StudentsImport;
use App\Models\Semester;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('import')
                ->label('Import Students')
                ->color('primary')
                ->schema([
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
        ];
    }
}
