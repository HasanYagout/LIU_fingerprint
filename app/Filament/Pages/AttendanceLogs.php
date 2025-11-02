<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Collection;

class AttendanceLogs extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-list-bullet';
    protected static ?string $navigationLabel = 'Attendance Logs (API)';
    protected  string $view = 'filament.pages.attendance-logs';


    public function getTableRecords(): Collection
    {
        $response = Http::get('http://127.0.0.1:8001/api/local-data');
        dd($response->json());
        $records = collect($response->successful() ? $response->json() : []);

        return $records;
    }


    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('C_date')
                ->label('Date')
                ->searchable(),
            Tables\Columns\TextColumn::make('c_time')
                ->label('Time')
                ->searchable(),
            Tables\Columns\TextColumn::make('c_name')
                ->label('Name')
                ->searchable(),
            Tables\Columns\TextColumn::make('c_unique')
                ->label('Unique ID')
                ->searchable(),
            Tables\Columns\TextColumn::make('l_result')
                ->label('Result')
                ->searchable(),
            Tables\Columns\TextColumn::make('l_tid')
                ->label('TID')
                ->searchable(),
        ];
    }

    // Optional: Add table configuration
    protected function getTableHeaderActions(): array
    {
        return [
            // You can add header actions here if needed
        ];
    }

    // This tells Filament which field to use as the unique key
    public function getTableRecordKey($record): string
    {
        return $record['c_unique'];
    }
}
