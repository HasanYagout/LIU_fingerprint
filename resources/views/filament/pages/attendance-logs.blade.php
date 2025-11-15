<x-filament::page>


    <div class="space-y-6">
        <form wire:submit.prevent="search" class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <div>
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

    </div>
</x-filament::page>
