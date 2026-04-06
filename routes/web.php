<?php

use App\Http\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;

Route::get('category',[CategoryController::class,'index'])->name('category.show');
Route::get('category-form',[CategoryController::class,'form'])->name('category.form');
Route::post('category',[CategoryController::class,'save'])->name('category.save');

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});
