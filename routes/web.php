<?php

use App\Http\Controllers\DepositController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WithdrawController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [TransactionController::class, 'index'])->name('home')->middleware('auth');

Route::resource('users', UserController::class);
Route::resource('login', LoginController::class);
Route::resource('withdraw', WithdrawController::class);
Route::resource('deposit', DepositController::class);

Route::get('login-redirect', function(){
    return redirect()->route('login.index');
})->name('login');


