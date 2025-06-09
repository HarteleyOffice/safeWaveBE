<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

use App\Http\Controllers\ClientKeyController;

//return all keys
Route::apiResource('client-keys', ClientKeyController::class);

//connect device to available key (change the key availability = false)
Route::get('claim-key', [ClientKeyController::class, 'claimAvailableKey']);

//disconnect device from the key (change the key availability = true)
Route::post('disconnect-key', [ClientKeyController::class, 'disconnectKey']);

//store peers config to database
Route::post('import-peers-from-path', [ClientKeyController::class, 'importPeersFromCustomPath']);

//reset all keys' availability to "TRUE"
Route::post('reset-availability', [ClientKeyController::class, 'resetAllAvailability']);




