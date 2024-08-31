<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AssetController;

Route::get('/', function () {
    return view('home');
});

Route::get('/add-asset', function () {
    return view('add-asset'); // Nome do novo arquivo Blade
})->name('add-asset');

Route::get('/assets/current/{userId}', [AssetController::class, 'calcularDadosUsuario']);
Route::get('/assets', [AssetController::class, 'index']);
Route::post('/assets', [AssetController::class, 'store']);
Route::get('/assets/{id}', [AssetController::class, 'show']);
Route::put('/assets/{id}', [AssetController::class, 'update']);
Route::delete('/assets/{id}', [AssetController::class, 'destroy']);
Route::post('/assets/rebalance/user/{userId}', [AssetController::class, 'rebalanceUserWallet']);

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
