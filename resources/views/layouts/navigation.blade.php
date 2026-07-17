<nav x-data="{ open: false }" class="navbar">
    <!-- Primary Navigation Menu -->
    <div class="container">
        <div class="navbar-inner">
            <div class="navbar-brand">
                <!-- Logo -->
                <a href="{{ route('dashboard') }}">
                    <x-application-logo class="navbar-logo" />
                </a>

                <!-- Navigation Links -->
                <div class="nav-links">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    @if (Auth::user()->isAdmin())
                        <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                            {{ __('Dashboard') }}
                        </x-nav-link>
                        <x-nav-link :href="route('admin.registrations.index')" :active="request()->routeIs('admin.registrations.*')">
                            {{ __('Registrations') }}
                        </x-nav-link>
                        <x-nav-link :href="route('admin.guardian-flags')" :active="request()->routeIs('admin.guardian-flags')">
                            {{ __('Guardian Flags') }}
                        </x-nav-link>
                        <x-nav-link :href="route('admin.room-inventory')" :active="request()->routeIs('admin.room-inventory')">
                            {{ __('Rooms') }}
                        </x-nav-link>
                        <x-nav-link :href="route('admin.group-merge')" :active="request()->routeIs('admin.group-merge')">
                            {{ __('Merge Groups') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="user-menu">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="user-menu-trigger">
                            <div>{{ Auth::user()->name }}</div>

                            <div>
                                <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill="currentColor" fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <button @click="open = ! open" class="nav-toggle">
                <svg width="24" height="24" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                    <path x-show="! open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    <path x-show="open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div x-show="open" x-cloak class="mobile-nav">
        <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
            {{ __('Dashboard') }}
        </x-responsive-nav-link>

        @if (Auth::user()->isAdmin())
            <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('admin.registrations.index')" :active="request()->routeIs('admin.registrations.*')">
                {{ __('Registrations') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('admin.guardian-flags')" :active="request()->routeIs('admin.guardian-flags')">
                {{ __('Guardian Flags') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('admin.room-inventory')" :active="request()->routeIs('admin.room-inventory')">
                {{ __('Rooms') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('admin.group-merge')" :active="request()->routeIs('admin.group-merge')">
                {{ __('Merge Groups') }}
            </x-responsive-nav-link>
        @endif

        <!-- Responsive Settings Options -->
        <div class="mobile-nav-user">
            <div class="mobile-nav-user-name">{{ Auth::user()->name }}</div>
            <div class="mobile-nav-user-email">{{ Auth::user()->email }}</div>
        </div>

        <x-responsive-nav-link :href="route('profile.edit')">
            {{ __('Profile') }}
        </x-responsive-nav-link>

        <!-- Authentication -->
        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <x-responsive-nav-link :href="route('logout')"
                    onclick="event.preventDefault();
                                this.closest('form').submit();">
                {{ __('Log Out') }}
            </x-responsive-nav-link>
        </form>
    </div>
</nav>
