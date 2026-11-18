<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\InterviewController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return view('welcome');
})->name('home');

Route::view('/roadmap', 'roadmap')->name('roadmap');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/auth/google/redirect', [AuthController::class, 'redirectToGoogle'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [InterviewController::class, 'dashboard'])->name('dashboard');
    Route::get('/interviews/new', [InterviewController::class, 'create'])->name('interviews.create');
    Route::post('/interviews', [InterviewController::class, 'store'])->name('interviews.store');
    Route::get('/interviews/{interview}/session', [InterviewController::class, 'session'])->name('interviews.session');
    Route::post('/interviews/{interview}/submit', [InterviewController::class, 'submit'])->name('interviews.submit');
    Route::get('/interviews/{interview}/report', [InterviewController::class, 'report'])->name('interviews.report');
    Route::post('/interviews/{interview}/email', [InterviewController::class, 'sendEmail'])->name('interviews.email');
    Route::delete('/interviews/{interview}', [InterviewController::class, 'destroy'])->name('interviews.destroy');
});
