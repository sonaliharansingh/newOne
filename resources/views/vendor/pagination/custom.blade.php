@if ($paginator->hasPages())
    <nav style="display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
        @if ($paginator->onFirstPage())
            <span class="btn btn-secondary" style="opacity: 0.5;">&laquo; Previous</span>
        @else
            <button type="button" class="btn btn-secondary" wire:click="previousPage" wire:loading.attr="disabled">&laquo; Previous</button>
        @endif

        <span class="text-muted">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>

        @if ($paginator->hasMorePages())
            <button type="button" class="btn btn-secondary" wire:click="nextPage" wire:loading.attr="disabled">Next &raquo;</button>
        @else
            <span class="btn btn-secondary" style="opacity: 0.5;">Next &raquo;</span>
        @endif
    </nav>
@endif
