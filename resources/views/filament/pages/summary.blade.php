@props([
    'paginator',
    'recordsPerPageSelectOptions' => [],
])

<div class="filament-tables-pagination-summary px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
    Showing
    <span class="font-medium">{{ $paginator->firstItem() }}</span>
    to
    <span class="font-medium">{{ $paginator->lastItem() }}</span>
    of
    <span class="font-medium">{{ $paginator->total() }}</span>
    records
</div>