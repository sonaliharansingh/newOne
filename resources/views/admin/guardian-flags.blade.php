<x-app-layout>
    <x-slot name="header">
        <h2 class="page-heading">{{ __('Guardian Flags') }}</h2>
    </x-slot>

    <div class="page-body">
        <div class="container">
            <livewire:admin.guardian-flag-queue />
        </div>
    </div>
</x-app-layout>
