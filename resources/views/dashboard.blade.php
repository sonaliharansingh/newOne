<x-app-layout>
    <x-slot name="header">
        <h2 class="page-heading">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="page-body">
        <div class="container">
            <div class="panel">
                <div class="panel-body">
                    <p class="text-muted" style="margin-bottom: 1rem;">{{ __("You're logged in!") }}</p>
                    <a href="{{ route('registration.wizard') }}" class="btn btn-primary">Register for Pilgrimage</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
