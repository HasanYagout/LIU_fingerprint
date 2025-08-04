<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Carbon;

class Dashboard extends BaseDashboard
{
    use BaseDashboard\Concerns\HasFiltersForm;
    protected ?string $maxContentWidth = 'full';

    public static function getNavigationLabel(): string
    {
        return auth()->user()->hasRole('manager') ? 'Monitoring' : 'Dashboard';
    }

    public function mount(): void
    {
        // Only fill the form if user has permission to see filters
        if ($this->shouldShowFilters()) {
            $this->form->fill([
                'startDate' => now(),
                'endDate' => now()->endOfMonth(),
            ]);
        }
    }

    public function filtersForm(Form $form): Form
    {
        // Return empty form if user shouldn't see filters
        if (!$this->shouldShowFilters()) {
            return $form->schema([]);
        }

        return $form
            ->schema([
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
                    ->columns(2),
            ]);
    }

    protected function shouldShowFilters(): bool
    {
        // Define your condition here - example: only managers can see filters
        return !auth()->user()->hasRole('Accountant');
    }
}
