<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div class="field">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <!-- Password -->
        <div class="field">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" />
        </div>

        <!-- Remember Me -->
        <div class="field">
            <label for="remember_me" class="checkbox-label">
                <input id="remember_me" type="checkbox" name="remember">
                <span>{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="form-row-end">
            @if (Route::has('password.request'))
                <a class="inline-link" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button>
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
