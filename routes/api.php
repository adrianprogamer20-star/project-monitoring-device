<?php

use App\Http\Controllers\DeviceController;
use Illuminate\Support\Facades\Route;

// Endpoint yang dipanggil oleh script Python (agent monitoring) di setiap PC lab
Route::post('/device/ping', [DeviceController::class, 'ping']);
Route::post('/device/screenshot', [DeviceController::class, 'screenshot']);