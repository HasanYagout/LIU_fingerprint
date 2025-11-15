<?php

namespace App\Filament\Resources\Students;

use App\Filament\Resources\Students\Pages\CreateStudent;
use App\Filament\Resources\Students\Pages\EditStudent;
use App\Filament\Resources\Students\Pages\ListStudents;
use App\Filament\Resources\Students\Pages\ViewStudent;
use App\Filament\Resources\Students\RelationManagers\ExceptionsRelationManager;
use App\Filament\Resources\Students\RelationManagers\SemestersRelationManager;
use App\Filament\Resources\Students\Schemas\StudentForm;
use App\Filament\Resources\Students\Schemas\StudentInfolist;
use App\Filament\Resources\Students\Tables\StudentsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Models\Student;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;
    protected static int $globalSearchResultsLimit = 10;


    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function canAccess(): bool
    {

        return auth()->user() && auth()->user()->hasPermissionTo('View:Student');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name','student_id'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string | Htmlable
    {

        return $record->name . ' | '. $record->student_id . ' | ' . $record->major;
    }
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Name'=>$record->name,
            'student_id'=>$record->student_id,
            'major'=>$record->major
        ];
    }


    public static function getGlobalSearchResultUrl(Model $record): ?string
    {
        return static::getUrl('view', ['record' => $record]);
    }

    public static function form(Schema $schema): Schema
    {
        return StudentForm::configure($schema);
    }


    public static function infolist(Schema $schema): Schema
    {
        return StudentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StudentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            SemestersRelationManager::class,
            ExceptionsRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStudents::route('/'),
            'create' => CreateStudent::route('/create'),
            'view' => ViewStudent::route('/{record}'),
            'edit' => EditStudent::route('/{record}/edit'),
        ];
    }
}
