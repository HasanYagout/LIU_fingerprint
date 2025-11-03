<x-filament-panels::page>
    <div class="space-y-6">
        <form wire:submit.prevent="search" class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{ $this->form }}
            </div>

            <div class="flex justify-end mt-4">
                <x-filament::button
                    type="submit"
                    :loading="$loading"
                    icon="heroicon-o-magnifying-glass"
                    color="primary"
                >
                    Search
                </x-filament::button>
            </div>
        </form>

        {{ $this->table }}

        @if ($this->getTableRecords()->count())
            <div class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                Showing
                <span class="font-medium">{{ $this->getTableRecords()->firstItem() }}</span>
                to
                <span class="font-medium">{{ $this->getTableRecords()->lastItem() }}</span>
                of
                <span class="font-medium">{{ $this->getTableRecords()->total() }}</span>
                records
            </div>
        @endif
    </div>
{{--    {{ $this->table }}--}}
</x-filament-panels::page>
