<?php

namespace App\Filament\Resources\StudentExceptions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StudentExceptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.student_id')
                    ->searchable(),
                TextColumn::make('student.name')
                    ->searchable(),
                TextColumn::make('semester.name')
                    ->label('Semester'),
                TextColumn::make('user.name')
                    ->badge()
                    ->label('Created By'),
                TextColumn::make('from_date')
                    ->date(),
                TextColumn::make('to_date')
                    ->date(),
                TextColumn::make('reason')
                    ->toggleable()
                    ->limit(50),
                TextColumn::make('created_at')
                    ->toggleable()
                    ->dateTime(),
            ])
            ->filters([
                Filter::make('from_date_range')
                    ->schema([
                        DatePicker::make('from_date_from')
                            ->label('From Date From'),
                        DatePicker::make('from_date_to')
                            ->label('From Date To'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from_date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('from_date', '>=', $date),
                            )
                            ->when(
                                $data['from_date_to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('from_date', '<=', $date),
                            );
                    }),
                SelectFilter::make('user')
                    ->label('Created By')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
//                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
//                    ForceDeleteBulkAction::make(),
//                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
