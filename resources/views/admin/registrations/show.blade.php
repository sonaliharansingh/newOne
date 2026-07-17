<x-app-layout>
    <x-slot name="header">
        <h2 class="page-heading">{{ __('Registration Detail') }}</h2>
    </x-slot>

    <div class="page-body">
        <div class="container">
            <livewire:admin.registration-show :group-id="$group->id" />
        </div>
    </div>
</x-app-layout>
