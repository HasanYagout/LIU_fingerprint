<?php

namespace App\Filament\Pages;

use App\Models\AttendanceLog;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Support\Collection;

class AttendanceLogs extends Page implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-list-bullet';
    protected static ?string $navigationLabel = 'Attendance Logs (API)';
    protected string $view = 'filament.pages.attendance-logs';

    public $loading = false;
    public $date;
    public $student_id;

    public function mount(): void
    {
        $this->date = request('date')
            ? Carbon::parse(request('date'))->format('Y-m-d')
            : now()->format('Y-m-d');

        // also set form value so the DatePicker shows it
        $this->form->fill([
            'date' => $this->date,
            'student_id' => $this->student_id,
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            DatePicker::make('date')
                ->default(now())
                ->reactive()
                ->afterStateUpdated(function ($state) {
                    $this->date = $state;
                    $this->resetPage(); // Reset to first page when filters change
                }),
            TextInput::make('student_id')
                ->label('Student ID')
                ->numeric()
                ->reactive()
                ->placeholder('Enter student ID')
                ->afterStateUpdated(function ($state) {
                    $this->student_id = $state;
                    $this->resetPage(); // Reset to first page when filters change
                }),
        ];
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('C_Date')
                ->formatStateUsing(fn($state) => Carbon::createFromFormat('Ymd', $state)->format('Y-m-d'))
                ->label('Date'),
            Tables\Columns\TextColumn::make('C_Time')
                ->formatStateUsing(fn($state) => Carbon::createFromFormat('His', $state)->format('H:i:s'))
                ->label('Time'),
            Tables\Columns\TextColumn::make('C_Name')->label('Name'),
            Tables\Columns\TextColumn::make('C_Unique')->label('Unique ID'),
            Tables\Columns\TextColumn::make('L_Result')
                ->label('Result')
                ->formatStateUsing(fn($state) => $state === 0 ? 'Entry' : 'No Permission')
                ->badge()
                ->color(fn($state) => $state === 0 ? 'success' : 'danger'),
            Tables\Columns\TextColumn::make('L_TID')->label('TID'),
        ];
    }



    // Return Collection of API logs
    public function getTableRecords(): Collection
    {
        $page = $this->getTablePage();
        $perPage = $this->getTableRecordsPerPage();

        // Use Livewire properties directly
        $date = $this->date ?? now()->format('Y-m-d');
        $studentId = $this->student_id ?? null;

        // Debug: Log what we're sending to the API
        \Log::info('Search Parameters:', [
            'date' => $date,
            'student_id' => $studentId,
            'page' => $page,
            'perPage' => $perPage
        ]);

        // Convert student_id to string if it's not empty
        $studentId = !empty($studentId) ? (string)$studentId : null;

        AttendanceLog::setSearchParameters(
            $date,
            $studentId,
            $page,
            $perPage
        );

        // Get the paginator from the model
        $paginator = (new AttendanceLog())->getRowsPaginated();

        return collect($paginator->items());
    }

    // Optional: Table unique key
    public function getTableRecordKey($record): string
    {
        return $record['L_UID'] . '_' . $record['C_Date'] . '_' . $record['C_Time'];
    }

    protected function getTablePaginationView(): string
    {
        return 'filament.pages.summary';
    }

    protected function getTableHeaderActions(): array
    {
        return [];
    }

    // Search method
    public function search(): void
    {
        $this->loading = true;
        $this->resetPage(); // Reset to first page when searching
        $this->loading = false;
    }

    // Helper method to get pagination info for the view
    public function getPaginationInfo(): array
    {
        $page = $this->getTablePage();
        $perPage = $this->getTableRecordsPerPage();

        // Use Livewire properties directly
        $date = $this->date ?? now()->format('Y-m-d');
        $studentId = $this->student_id ?? null;
        $studentId = !empty($studentId) ? (string)$studentId : null;

        AttendanceLog::setSearchParameters(
            $date,
            $studentId,
            $page,
            $perPage
        );

        $paginator = (new AttendanceLog())->getRowsPaginated();

        return [
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'currentPage' => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
        ];
    }
}
