<?php

use App\Http\Controllers\DeviceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/devices');
});

Route::get('/devices', [DeviceController::class, 'index'])->name('devices.index');
Route::get('/devices/data', [DeviceController::class, 'data'])->name('devices.data');

// PENTING: route /devices/data (di atas) harus tetap didaftarkan SEBELUM /devices/{device}
// supaya "data" tidak salah tertangkap sebagai parameter {device}.
Route::get('/devices/{device}', [DeviceController::class, 'show'])->name('devices.show');
Route::get('/devices/{device}/data', [DeviceController::class, 'showData'])->name('devices.show.data');
Route::post('/devices/{device}/watch', [DeviceController::class, 'watchStart'])->name('devices.watch');
Route::post('/devices/{device}/command', [DeviceController::class, 'command'])->name('devices.command');