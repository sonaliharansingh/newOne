<x-app-layout>
    <x-slot name="header">
        <h2 class="page-heading">{{ __('Admin Dashboard') }}</h2>
    </x-slot>

    <div class="page-body">
        <div class="container">
            <livewire:admin.dashboard />
        </div>
    </div>
</x-app-layout>
