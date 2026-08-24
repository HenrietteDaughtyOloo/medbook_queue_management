<?php

use Illuminate\Http\Request;
use App\Http\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');


Route::get('/queue', [CustomerController::class, 'index']);
Route::post('/customers', [CustomerController::class, 'store']);
Route::patch('/customers/{customer}/status', [CustomerController::class, 'updateStatus']);
