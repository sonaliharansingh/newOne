<x-app-layout>
    <x-slot name="header">
        <h2 class="page-heading">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="page-body">
        <div class="container stack">
            <div class="panel">
                <div class="panel-body">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="panel">
                <div class="panel-body">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="panel">
                <div class="panel-body">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
