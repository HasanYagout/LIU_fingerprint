<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SemesterResource\Pages;
use App\Filament\Resources\SemesterResource\RelationManagers;
use App\Models\Semester;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SemesterResource extends Resource
{
    protected static ?string $model = Semester::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                ->required(),
                TextInput::make('year')
                ->required(),
                Forms\Components\DatePicker::make('start_date')
                    ->required(),

                Forms\Components\DatePicker::make('midterm_date')
                    ->required()
                    ->after('start_date'),

                Forms\Components\DatePicker::make('end_date')
                    ->required()
                    ->after('midterm_date'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('year'),
                Tables\Columns\TextColumn::make('start_date')
                ->formatStateUsing(function ($state) {
                   return Carbon::parse($state)->format('Y-m-d');
                }),
                Tables\Columns\TextColumn::make('midterm_date')
                    ->formatStateUsing(function ($state) {
                        return Carbon::parse($state)->format('Y-m-d');
                    }),
                Tables\Columns\TextColumn::make('end_date')
                    ->formatStateUsing(function ($state) {
                        return Carbon::parse($state)->format('Y-m-d');
                    }),
                Tables\Columns\ToggleColumn::make('status')
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
                SelectFilter::make('year')
                    ->label('Year')
                    ->options(function () {
                        return Semester::query()
                            ->select('year')  // Select only the year column
                            ->distinct()      // Get distinct years
                            ->orderBy('year', 'desc')  // Order by year descending
                            ->pluck('year', 'year')    // Use year for both key and value
                            ->toArray();
                    })
                    ->query(function (Builder $query, $data) {
                        if (!$data['value']) {
                            return;
                        }

                        $query->where('semesters.year', $data['value']);
                    })
                    ->searchable(),  // Make it searchable if you have many years
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

            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSemesters::route('/'),
            'create' => Pages\CreateSemester::route('/create'),
            'edit' => Pages\EditSemester::route('/{record}/edit'),
        ];
    }
}
