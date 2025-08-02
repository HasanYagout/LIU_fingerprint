<?php

namespace App\Filament\Pages;

use App\Models\AttendanceLog;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Support\Collection;

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
    public $logs = [];

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
                }),
        ];
    }

    protected function getTableQuery()
    {

        return AttendanceLog::query();
    }

    protected function getTableData(): array
    {
        return $this->logs;
    }
    public function mount(): void
    {
        $this->date = request()->query('date');
        $this->studentId = request()->query('studentId');

        if ($this->date) {
            $this->search();
        }
    }

    public function search()
    {
        $this->validate([
            'date' => 'required|date',
            'studentId' => 'nullable|numeric',
        ]);

        $this->loading = true;
        $this->error = null;
        $this->logs = [];

        try {
            $payload = [
                'date' => Carbon::parse($this->date)->format('Ymd'),
            ];

            if (!empty($this->studentId)) {
                $payload['uniqueId'] = $this->studentId;
            }

            $response = Http::withBasicAuth(
                config('services.api.username'),
                config('services.api.password')
            )->post('http://172.170.17.5:2001/api/v1/attendance-logs',$payload);

            if ($response->successful()) {
                $data = $response->json();
                $this->logs = $data['logs'] ?? [];
            } else {
                \Log::error('Attendance API Error', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'request' => [
                        'date' => $this->date,
                        'studentId' => $this->studentId
                    ],
                    'url' => 'http://172.170.17.5:2001/api/v1/attendance-logs'
                ]);
                $this->error = 'Failed to fetch data: ' . $response->status();
            }
        } catch (\Exception $e) {
            $this->error = 'Error: ' . $e->getMessage();
        } finally {
            $this->loading = false;
        }
    }

    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [10, 25, 50, 100];
    }
}
