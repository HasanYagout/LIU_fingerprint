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
use Illuminate\Pagination\LengthAwarePaginator;

class AttendanceLogs extends Page implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-list-bullet';
    protected static ?string $navigationLabel = 'Attendance Logs (API)';
    protected string $view = 'filament.pages.attendance-logs';

    public $loading = false;

    protected function getFormSchema(): array
    {
        return [
            DatePicker::make('date')->default(now()),
            TextInput::make('studentId')->label('Student ID')->numeric(),
        ];
    }

    public function mount(): void
    {
        $this->form->fill([
            'date' => request()->query('date', now()->format('Y-m-d')),
            'studentId' => request()->query('studentId'),
        ]);
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
        $perPage = AttendanceLog::$itemsPerPage;

        // Set search parameters in the model
        AttendanceLog::setSearchParameters(
            $this->form->getState('date'),
            $this->form->getState('studentId'),
            $page,
            $perPage
        );

        // Fetch rows via the model
        $logs = (new AttendanceLog())->getRows();
        $total = AttendanceLog::$totalRecords;

        // Wrap in LengthAwarePaginator
        $paginator = new LengthAwarePaginator(
            $logs,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        // Filament expects Collection, so return $paginator->items() as Collection
        return collect($paginator->items());
    }

    // Optional: Table unique key
    public function getTableRecordKey($record): string
    {
        return $record['L_UID'];
    }

    protected function getTablePaginationView(): string
    {
        return view('filament.pages.summary'); // your custom pagination blade
    }

    protected function getTableHeaderActions(): array
    {
        return [];
    }
}
