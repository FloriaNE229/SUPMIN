<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Dashboard\Controllers\DashboardController;

Route::get('/dashboard', [DashboardController::class, 'index']);