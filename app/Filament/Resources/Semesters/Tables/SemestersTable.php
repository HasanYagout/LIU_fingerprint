<?php

namespace App\Filament\Resources\Semesters\Tables;

use App\Models\Semester;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SemestersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                ->searchable(),
                TextColumn::make('start_date')
                    ->date('Y-m-d'),
                TextColumn::make('midterm_date')
                    ->date('Y-m-d'),
                TextColumn::make('end_date')
                    ->date('Y-m-d'),
                ToggleColumn::make('status')
                    ->updateStateUsing(function (Semester $record, $state) {
                        // Set all other semesters to inactive
                        Semester::where('id', '!=', $record->id)->update(['status' => 0]);

                        // Update the current record
                        $record->status = $state;
                        $record->save();

                        return $state;
                    }),
            ])
            ->filters([
                SelectFilter::make('semester_name')
                    ->label('Semester Name')
                    ->options(function () {
                        return Semester::query()
                            ->select('name')
                            ->distinct()
                            ->orderBy('name')
                            ->pluck('name', 'name')
                            ->toArray();
                    })
                    ->query(function (Builder $query, $data) {
                        if (!$data['value']) {
                            return;
                        }
                        $query->where('semesters.name', 'like', "%{$data['value']}%");
                    })
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
