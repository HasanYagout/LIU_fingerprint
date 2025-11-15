<?php

namespace App\Filament\Pages;

use App\Models\AttendanceLog;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Filament\Support\Enums\MaxWidth;


class AttendanceLogs extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;
    use HasPageShield;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-rectangle-stack';
    protected  string $view = 'filament.pages.attendance-logs';
    protected static ?string $navigationLabel = 'Attendance';

    public $date;
    public $studentId;
    public $loading = false;
    public $error = null;

    public function getMaxContentWidth(): string|null|\Filament\Support\Enums\Width
    {
        return 'full';
    }


    protected function getFormSchema(): array
    {
        return [
            Grid::make(2) // 2 columns
            ->schema([
                DatePicker::make('date')
                    ->required()
                    ->columnSpan(1)
                    ->default(now()),
                TextInput::make('studentId')
                    ->columnSpan(1)
                    ->label('Student ID')
                    ->numeric(),
            ])->columnSpanFull(),
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
                ,
            Tables\Columns\TextColumn::make('L_UID')
                ->formatStateUsing(fn ($state) => (string)$state==-1?'Not Registered':$state)
                ->label('Student ID')
               ,
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

    protected function getTablePaginationView(): string
    {
        return view('filament.pages.summary');
    }

    protected function paginateTableQuery(Builder $query): LengthAwarePaginator
    {
        $page = $this->getTablePage();
        $perPage = $this->getTableRecordsPerPage();

        AttendanceLog::setSearchParameters(
            $this->date,
            $this->studentId,
            $page,
            $perPage
        );

        AttendanceLog::clearBootedModels();

        $items = AttendanceLog::all();

        if (AttendanceLog::$lastError) {
            Notification::make()
                ->title('API Error')
                ->body(AttendanceLog::$lastError)
                ->danger()
                ->persistent() // stays until closed
                ->send();
        }

        $items = $items->sortByDesc(fn($i) => $i->C_Date . $i->C_Time)->values();

        return new LengthAwarePaginator(
            $items,
            AttendanceLog::$totalRecords,
            $perPage,
            $page
        );
    }




    protected function getTableRecordsPerPageSelectOptions(): array
    {
        // Use the API's page size as the maximum
        $apiPageSize = AttendanceLog::$apiPageSize ?? 100;


        // Standard options that are <= API's max size
        $options = [100, 250, 500];

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
        return AttendanceLog::$apiPageSize ?? $options[0] ?? 100;
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
    // Hide table search field

    protected function getTableSearchEnabled(): bool
    {
        return false;
    }



}
