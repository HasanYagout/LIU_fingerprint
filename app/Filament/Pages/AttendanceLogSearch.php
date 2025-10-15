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
use Filament\Support\Enums\MaxWidth;


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

    public function getMaxContentWidth(): MaxWidth|string|null
    {
        return 'full';
    }

    public static function canAccess(): bool
    {
        return  auth()->user() &&  auth()->user()->hasPermissionTo('page_AttendanceLogSearch');
    }
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
            Tables\Columns\TextColumn::make('L_Result')
                ->label('Mode')
                ->formatStateUsing(function ($state) {
                    return match($state) {
                        0 => 'Entry',
                        default => 'No Permission',
                    };
                })
                ->badge()
                ->color(function ($state) {
                    return match($state) {
                        0 => 'success',  // Green for Entry
                        default => 'danger', // Gray for No Permission
                    };
                }),
            Tables\Columns\TextColumn::make('L_TID')
                ->label('Terminal')
            ,
        ];
    }

    protected function getTableQuery(): Builder
    {

        return AttendanceLog::query(); // Dummy query to satisfy Filament
    }

    protected function paginateTableQuery(Builder $query): LengthAwarePaginator
    {
        $page = $this->getTablePage();
        $perPage = $this->getTableRecordsPerPage();

        // Set the parameters for the API call
        AttendanceLog::setSearchParameters(
            $this->date,
            $this->studentId,
            $page,
            $perPage
        );

        // Clear cached data and fetch fresh results
        AttendanceLog::clearBootedModels();
        $items = AttendanceLog::all();
        // Sort the items by C_Date and C_Time in descending order
        $items = $items->sortByDesc(function ($item) {
            return $item->C_Date . $item->C_Time;
        })->values();

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

    protected function getTablePaginationView(): string
    {
        return view('filament.pages.summary');
    }

    protected function getTableRecordsPerPageSelectOptions(): array
    {
        // Use the API's page size as the maximum
        $apiPageSize = AttendanceLog::$apiPageSize ?? 100;


        // Standard options that are <= API's max size
        $options = [10, 25, 50];

        // Add the API page size if it's not already included
        if (!in_array($apiPageSize, $options)) {
            $options[] = $apiPageSize;
        }

        // Sort and return
        sort($options);
        return $options;
    }

    public function getTableRecordsPerPage(): int
    {
        // Get the first available option or fallback to 10
        $options = $this->getTableRecordsPerPageSelectOptions();
        return AttendanceLog::$apiPageSize ?? $options[0] ?? 10;
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

    protected function getTablePaginationEnabled(): bool
    {
        return true;
    }

    protected function getTablePaginationSummaryEnabled(): bool
    {
        return true;
    }


}
