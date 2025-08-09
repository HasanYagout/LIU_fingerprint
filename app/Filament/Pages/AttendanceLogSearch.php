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
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class AttendanceLogSearch extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static string $view = 'filament.pages.attendance-log-search';
    protected static ?string $navigationLabel = 'Attendance';

    public $date;
    public $studentId;
    public $loading = false;
    public $error = null;

    protected function getFormSchema(): array
    {
        return [
            DatePicker::make('date')
                ->required()
                ->default(now()),
            TextInput::make('studentId')
                ->label('Student ID')
                ->numeric(),
        ];
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('C_Date')
                ->label('Date')
                ->formatStateUsing(fn ($state) => Carbon::createFromFormat('Ymd', $state)->format('Y-m-d')),
            Tables\Columns\TextColumn::make('C_Time')
                ->label('Time')
                ->formatStateUsing(fn ($state) => Carbon::createFromFormat('His', $state)->format('H:i:s')),
            Tables\Columns\TextColumn::make('C_Name')
                ->label('Name')
                ->searchable(),
            Tables\Columns\TextColumn::make('C_Unique')
                ->label('Student ID')
                ->searchable(),
            Tables\Columns\TextColumn::make('L_Mode')
                ->label('Mode')
                ->formatStateUsing(function ($state) {
                    return match($state) {
                        1 => 'Entry',
                        3 => 'Exit',
                        default => $state,
                    };
                }),
            Tables\Columns\TextColumn::make('L_Result')
                ->label('Result')
                ->formatStateUsing(function ($state) {
                    return match($state) {
                        0 => 'Success',
                        3 => 'Failed',
                        default => $state,
                    };
                })
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    '0' => 'success',
                    '3' => 'danger',
                    default => 'gray',
                }),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return AttendanceLog::query(); // Dummy query to satisfy Filament
    }

    protected function paginateTableQuery(Builder $query): LengthAwarePaginator
    {
        $page = $this->getTablePage(); // ✅ Correct way to get Livewire-managed page number
        $perPage = $this->getTableRecordsPerPage();

        AttendanceLog::setSearchParameters($this->date, $this->studentId, $page, $perPage);
        AttendanceLog::clearBootedModels(); // To refresh Sushi data

        $items = AttendanceLog::all(); // Fetched from API
        $total = AttendanceLog::$totalRecords;

        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
    }

    public function mount(): void
    {
        $this->form->fill([
            'date' => request()->query('date', now()->format('Y-m-d')),
            'studentId' => request()->query('studentId'),
        ]);
    }

    public function search()
    {
        $this->validate([
            'date' => 'required|date',
            'studentId' => 'nullable|numeric',
        ]);

        $this->loading = true;
        $this->error = null;

        $this->resetPage();
        $this->loading = false;
    }



    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [10, 25, 50, 100];
    }
}
