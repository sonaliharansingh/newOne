<x-app-layout>
    <x-slot name="header">
        <h2 class="page-heading">
            {{ __('Guest Registration') }}
        </h2>
    </x-slot>

    <div class="page-body">
        <div class="container">
            <livewire:group-registration-wizard />
        </div>
    </div>
</x-app-layout>
