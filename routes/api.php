<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MainController;
use App\Http\Controllers\PaypalController;
use App\Http\Controllers\CoinPaymentsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('apikey.check')->group(function(){
    Route::get('test', [MainController::class, 'test']);
    Route::post('shard/topup', [MainController::class, 'topupShard']);
});

Route::prefix('callback')->group(function(){
    Route::post('paypal', [PaypalController::class, 'callback']);
    Route::post('coinpayments', [CoinPaymentsController::class, 'callback']);
});
