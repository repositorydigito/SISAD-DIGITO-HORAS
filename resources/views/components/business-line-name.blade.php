
<div class="flex items-center justify-between">
    <span>{{ $name }}</span>
    @if($canDelete)
        <button 
            type="button"
            wire:click="deleteLine({{ $lineId }})"
            class="text-danger-500 hover:text-danger-600"
        >
            <x-heroicon-m-trash class="w-4 h-4" />
        </button>
    @endif
</div>