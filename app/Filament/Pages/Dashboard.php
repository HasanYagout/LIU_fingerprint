<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Forms\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

class Dashboard extends BaseDashboard
{
    use BaseDashboard\Concerns\HasFiltersForm;
//    protected string|\Filament\Support\Enums\Width|null $maxContentWidth = 'full';
    protected int | string | array $columnSpan = 'full';


    public static function getNavigationLabel(): string
    {
        return auth()->user()->hasRole('manager') ? 'Monitoring' : 'Dashboard';
    }

    public function mount(): void
    {
        // Only fill the form if user has permission to see filters
        if ($this->shouldShowFilters()) {
            $this->getFiltersForm()->fill([
                'startDate' => now()->startOfMonth()->format('Y-m-d'),
                'endDate' => now()->endOfMonth()->format('Y-m-d'),
            ]);
        }
    }

    public function filtersForm(Schema $schema): Schema
    {
        // Return empty form if user shouldn't see filters
        if (!$this->shouldShowFilters()) {
            return $schema;
        }

        return $schema
            ->components([
                Section::make('Filters')
                    ->schema([
                        DatePicker::make('startDate')
                            ->label('Start Date')
                            ->native(false)
                            ->default(now()->startOfMonth()->format('Y-m-d'))
                            ->formatStateUsing(fn ($state) => Carbon::parse($state)->format('Y-m-d')),

                        DatePicker::make('endDate')
                            ->label('End Date')
                            ->native(false)
                            ->default(now()->endOfMonth())
                            ->formatStateUsing(fn ($state) => Carbon::parse($state)->format('Y-m-d')),
                    ])
                    ->extraAttributes(['class' => 'shadow-2xl'])
                ,
            ]);
    }

    protected function shouldShowFilters(): bool
    {
        // Define your condition here - example: only managers can see filters
        return !auth()->user()->hasRole('Accountant');
    }
}
