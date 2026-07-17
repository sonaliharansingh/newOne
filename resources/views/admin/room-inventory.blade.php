<x-app-layout>
    <x-slot name="header">
        <h2 class="page-heading">{{ __('Room Inventory') }}</h2>
    </x-slot>

    <div class="page-body">
        <div class="container">
            <livewire:admin.room-inventory />
        </div>
    </div>
</x-app-layout>
