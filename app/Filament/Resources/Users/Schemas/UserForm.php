<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\MultiSelect;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User Information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('password')
                            ->password()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn ($state) => filled($state))
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->maxLength(255),
                    ])->columns(2),

                Section::make('Role Assignment')
                    ->schema([
                        MultiSelect::make('roles')
                            ->label('Roles')
                            ->options(\Spatie\Permission\Models\Role::all()->pluck('name', 'id'))
                            ->relationship('roles', 'name'),
                    ]),
            ]);
    }
}
