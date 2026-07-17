<x-app-layout>
    <x-slot name="header">
        <h2 class="page-heading">{{ __('Registrations') }}</h2>
    </x-slot>

    <div class="page-body">
        <div class="container">
            <livewire:admin.registrations-index />
        </div>
    </div>
</x-app-layout>
