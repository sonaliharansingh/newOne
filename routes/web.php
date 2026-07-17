<?php

use App\Http\Controllers\ProfileController;
use App\Models\Group;
use App\Models\Hotel;
use App\Models\Room;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $hotels = Hotel::query()
        ->withCount('rooms')
        ->with(['rooms' => fn ($query) => $query->orderBy('room_number')])
        ->orderBy('hotel_name')
        ->get();

    $stats = [
        'hotels' => $hotels->count(),
        'rooms' => Room::count(),
        'available' => Room::where('room_status', '!=', 'maintenance')->sum('available_count'),
        'capacity' => Room::sum('capacity'),
    ];

    return view('welcome', [
        'hotels' => $hotels,
        'stats' => $stats,
    ]);
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/register-pilgrim', function () {
        return view('registration.wizard');
    })->name('registration.wizard');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    Route::get('/registrations', function () {
        return view('admin.registrations.index');
    })->name('registrations.index');

    Route::get('/registrations/{group}', function (Group $group) {
        return view('admin.registrations.show', ['group' => $group]);
    })->name('registrations.show');

    Route::get('/guardian-flags', function () {
        return view('admin.guardian-flags');
    })->name('guardian-flags');

    Route::get('/room-inventory', function () {
        return view('admin.room-inventory');
    })->name('room-inventory');

    Route::get('/group-merge', function () {
        return view('admin.group-merge');
    })->name('group-merge');
});

require __DIR__.'/auth.php';
