<?php

use App\Http\Controllers\Web\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('guest')->group(function () {
    // --- Register User (Umum) ---
    // Menampilkan form register
    Route::get('register', function () {
        return Inertia::render('Auth/Register');
    })->name('register');

    // Proses register (Menggunakan UserController yang sudah Anda buat)
    Route::post('register', [UserController::class, 'store']);

    // --- Login ---
    // Menampilkan halaman login
    Route::get('login', function () {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    })->name('login');

    // --- Lupa Password ---
    Route::get('forgot-password', function () {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    })->name('password.request');

    // --- Reset Password ---
    Route::get('reset-password/{token}', function ($token) {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'email' => request()->email,
        ]);
    })->name('password.reset');

    // --- Two Factor Challenge (Jika fitur 2FA aktif) ---
    Route::get('two-factor-challenge', function () {
        return Inertia::render('Auth/TwoFactorChallenge');
    })->name('two-factor.login');
});

Route::middleware('auth')->group(function () {
    // --- Verifikasi Email ---
    Route::get('verify-email', function () {
        return Inertia::render('Auth/VerifyEmail', [
            'status' => session('status'),
        ]);
    })->name('verification.notice');

    // --- Konfirmasi Password (untuk area sensitif) ---
    Route::get('confirm-password', function () {
        return Inertia::render('Auth/ConfirmPassword');
    })->name('password.confirm');

    // Catatan: Route POST untuk login, logout, reset-password, dll 
    // biasanya sudah ditangani otomatis oleh Laravel Fortify.
    // Kita hanya perlu mendefinisikan route GET (Tampilan) di sini 
    // jika Fortify 'views' dimatikan di config.
});
