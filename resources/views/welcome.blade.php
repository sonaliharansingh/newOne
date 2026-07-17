<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="{{ config('app.name', 'Sanctuary Stay') }} — verified pilgrimage lodging with real-time room availability, family-private rooms, women-only floors and elderly-friendly access.">

        <title>{{ config('app.name', 'Sanctuary Stay') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="landing">
        <div class="landing-shell">

            {{-- Navbar --}}
            <header class="landing-nav">
                <div class="container landing-nav-inner">
                    <a href="/" class="landing-brand">
                        <x-application-logo class="landing-brand-logo" />
                        <span>{{ config('app.name', 'Sanctuary Stay') }}</span>
                    </a>

                    <nav class="landing-nav-links">
                        <a href="#rooms">Rooms</a>
                        <a href="#amenities">Amenities</a>
                        <a href="#hotels">Locations</a>
                    </nav>

                    <div class="landing-nav-actions">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="btn btn-primary">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-secondary">Log in</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="btn btn-primary">Register</a>
                            @endif
                        @endauth
                    </div>
                </div>
            </header>

            {{-- Hero --}}
            <section class="landing-hero">
                <div class="container landing-hero-inner">
                    <span class="landing-eyebrow">Verified pilgrimage lodging</span>
                    <h1 class="landing-hero-title">Comfortable, safe stays for every pilgrim family</h1>
                    <p class="landing-hero-sub">
                        Browse real-time room availability across our partner hotels — private family rooms,
                        women-only floors, elderly-friendly access and full transparency before you register.
                    </p>

                    <div class="landing-hero-actions">
                        @auth
                            <a href="{{ route('registration.wizard') }}" class="btn btn-primary btn-lg">Start Registration</a>
                        @else
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="btn btn-primary btn-lg">Get Started</a>
                            @endif
                            <a href="#rooms" class="btn btn-secondary btn-lg">Browse Rooms</a>
                        @endauth
                    </div>

                    <div class="landing-stats">
                        <div class="landing-stat">
                            <span class="landing-stat-value">{{ $stats['hotels'] }}</span>
                            <span class="landing-stat-label">Partner Hotels</span>
                        </div>
                        <div class="landing-stat">
                            <span class="landing-stat-value">{{ $stats['rooms'] }}</span>
                            <span class="landing-stat-label">Rooms Listed</span>
                        </div>
                        <div class="landing-stat">
                            <span class="landing-stat-value">{{ $stats['available'] }}</span>
                            <span class="landing-stat-label">Beds Available</span>
                        </div>
                        <div class="landing-stat">
                            <span class="landing-stat-value">{{ $stats['capacity'] }}</span>
                            <span class="landing-stat-label">Total Capacity</span>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Amenities --}}
            <section id="amenities" class="landing-section">
                <div class="container">
                    <h2 class="landing-section-title">Built around family safety and comfort</h2>
                    <p class="landing-section-sub">Every room is tagged with the details that matter most before you travel.</p>

                    <div class="feature-grid">
                        <div class="feature-card">
                            <div class="feature-icon">🔒</div>
                            <h3>Private Family Rooms</h3>
                            <p class="text-muted">Rooms reserved exclusively for one family, never pooled with strangers.</p>
                        </div>
                        <div class="feature-card">
                            <div class="feature-icon">👩‍👧</div>
                            <h3>Women-Only Floors</h3>
                            <p class="text-muted">Dedicated floors for women travelling alone or in groups.</p>
                        </div>
                        <div class="feature-card">
                            <div class="feature-icon">🦯</div>
                            <h3>Elderly-Friendly Access</h3>
                            <p class="text-muted">Low-walk floors with lift access for elderly and mobility-limited guests.</p>
                        </div>
                        <div class="feature-card">
                            <div class="feature-icon">🛗</div>
                            <h3>Lift &amp; Staircase Access</h3>
                            <p class="text-muted">Every room lists its exact access route to the floor it sits on.</p>
                        </div>
                        <div class="feature-card">
                            <div class="feature-icon">📊</div>
                            <h3>Real-Time Availability</h3>
                            <p class="text-muted">Room status is live — no overbooking, no surprises at check-in.</p>
                        </div>
                        <div class="feature-card">
                            <div class="feature-icon">✅</div>
                            <h3>Verified Room Status</h3>
                            <p class="text-muted">Rooms under maintenance are automatically hidden from allocation.</p>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Rooms --}}
            <section id="rooms" class="landing-section landing-section-alt">
                <div class="container">
                    <h2 class="landing-section-title" id="hotels">Explore rooms by hotel</h2>
                    <p class="landing-section-sub">Live inventory across every partner property.</p>

                    @forelse ($hotels as $hotel)
                        <div class="hotel-block">
                            <div class="hotel-block-header">
                                <div>
                                    <h3 class="hotel-block-name">{{ $hotel->hotel_name }}</h3>
                                    <p class="text-muted">
                                        {{ $hotel->address }}, {{ $hotel->city }}{{ $hotel->state ? ', '.$hotel->state : '' }}
                                        &middot; {{ $hotel->total_floors }} floors
                                    </p>
                                </div>
                                <div class="hotel-block-badges">
                                    @if ($hotel->has_lift)
                                        <span class="badge badge-muted">Lift access</span>
                                    @endif
                                    @if ($hotel->has_staircase)
                                        <span class="badge badge-muted">Staircase access</span>
                                    @endif
                                    <span class="badge badge-success">{{ $hotel->rooms_count }} rooms</span>
                                </div>
                            </div>

                            <div class="room-grid">
                                @foreach ($hotel->rooms as $room)
                                    <div class="room-card room-card-public">
                                        <div class="room-card-header">
                                            <span class="room-card-number">Room {{ $room->room_number }}</span>
                                            <span class="badge {{ $room->room_status === 'available' ? 'badge-success' : ($room->room_status === 'maintenance' ? 'badge-danger' : 'badge-muted') }}">
                                                {{ ucfirst($room->room_status) }}
                                            </span>
                                        </div>

                                        <p class="room-card-type">{{ ucfirst($room->room_type) }} room</p>

                                        <div class="room-card-meta">
                                            <span>{{ $room->available_count }} / {{ $room->capacity }} beds free</span>
                                        </div>

                                        <div class="room-card-tags">
                                            @if ($room->is_private)
                                                <span class="badge badge-muted">Private</span>
                                            @endif
                                            @if ($room->women_only)
                                                <span class="badge badge-muted">Women only</span>
                                            @endif
                                            @if ($room->elderly_friendly)
                                                <span class="badge badge-muted">Elderly friendly</span>
                                            @endif
                                            @if ($room->lift_access)
                                                <span class="badge badge-muted">Lift</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="panel">
                            <div class="panel-body text-muted" style="text-align: center;">
                                Room inventory is being set up — check back shortly.
                            </div>
                        </div>
                    @endforelse
                </div>
            </section>

            {{-- CTA --}}
            <section class="landing-cta">
                <div class="container landing-cta-inner">
                    <h2>Ready to register your group?</h2>
                    <p class="text-muted">Create an account and start allocating rooms for your family or group in minutes.</p>
                    <div class="landing-hero-actions">
                        @auth
                            <a href="{{ route('registration.wizard') }}" class="btn btn-primary btn-lg">Start Registration</a>
                        @else
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="btn btn-primary btn-lg">Create Account</a>
                            @endif
                            <a href="{{ route('login') }}" class="btn btn-secondary btn-lg">Log in</a>
                        @endauth
                    </div>
                </div>
            </section>

            <footer class="landing-footer">
                <div class="container">
                    <p class="text-muted">&copy; {{ date('Y') }} {{ config('app.name', 'Sanctuary Stay') }}. All rights reserved.</p>
                </div>
            </footer>
        </div>
    </body>
</html>
