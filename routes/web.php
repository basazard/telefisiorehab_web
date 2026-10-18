<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::view('/', 'welcome');

// Route::view('dashboard', 'dashboard')
//     ->middleware(['auth', 'verified'])
//     ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// Route::middleware(['auth', 'verified'])->group(function () {
//     Route::middleware('role:patient')->prefix('patient')->name('patient.')->group(/* ... */);
//     Route::middleware('role:therapist,clinic_admin')->prefix('therapist')->name('therapist.')->group(/* ... */);
//     Route::middleware('role:clinic_admin')->prefix('clinic')->name('clinic.')->group(/* ... */);
// });

Route::middleware(['auth', 'verified'])->group(function () {

    // Breeze redirects to route('dashboard') after login, so keep the name and redirect by role
    Route::get('/dashboard', fn() => redirect()->route(Auth::user()->role->home()))
        ->name('dashboard');

    Route::middleware('role:patient')->prefix('patient')->name('patient.')->group(function () {
        Volt::route('profile/setup', 'pages.patient.profile-setup')->name('profile.setup');

        Route::middleware('profile.complete')->group(function () {
            Volt::route('screening/stopped', 'pages.patient.red-flag-stop')->name('red-flag.stop');   // must stay reachable while locked

            Route::middleware('redflag.clear')->group(function () {                                   // everything else is blocked while locked
                Volt::route('screening', 'pages.patient.screening')->name('screening');
                Volt::route('domain', 'pages.patient.domain')->name('domain');
                Volt::route('dashboard', 'pages.patient.dashboard')->name('dashboard');
                Volt::route('program', 'pages.patient.program')->name('program');
                Volt::route('history', 'pages.patient.history')->name('history');
            });

            Volt::route('dashboard', 'pages.patient.dashboard')->name('dashboard');
            Volt::route('program', 'pages.patient.program')->name('program');
            Volt::route('history', 'pages.patient.history')->name('history');
        });
    });

    Route::middleware('role:therapist,clinic_admin')->prefix('therapist')->name('therapist.')->group(function () {
        Volt::route('dashboard', 'pages.therapist.dashboard')->name('dashboard');
        Volt::route('patients', 'pages.therapist.patients')->name('patients');
        Volt::route('patients/{patient}', 'pages.therapist.patient-detail')->name('patients.show');
    });

    Route::middleware('role:clinic_admin')->prefix('clinic')->name('clinic.')->group(function () {
        Volt::route('dashboard', 'pages.clinic.dashboard')->name('dashboard');
        Volt::route('team', 'pages.clinic.team')->name('team');
    });

    Route::middleware('role:therapist,clinic_admin,platform_admin')->group(function () {
        Volt::route('staff/red-flags', 'pages.therapist.red-flag-review')->name('staff.red-flags');
    });
});

require __DIR__ . '/auth.php';
