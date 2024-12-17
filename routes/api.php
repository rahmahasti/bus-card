<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/card/create', [CardController::class, 'createCard']);
Route::get('/card/balance/{card_id}', [CardController::class, 'getBalance']);
Route::post('/card/topup', [CardController::class, 'topUp']);
Route::get('/card/transactions/{card_id}', [CardController::class, 'getTransactions']);
Route::post('/card/pay', [CardController::class, 'pay']);