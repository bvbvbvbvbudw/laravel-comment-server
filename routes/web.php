<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function(){
    return view('welcome');
});
//Route::get('/', [CommentsController::class, 'show'])->name('comments.show');
//Route::get('/comments/get', [CommentsController::class, 'index'])->name('comments.index'); // api

//Route::get('/comments/create', [CommentsController::class, 'create'])->name('comments.create');
//Route::post('/comments', [CommentsController::class, 'store'])->name('comments.store');
