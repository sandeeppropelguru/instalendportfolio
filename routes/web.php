<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Hubspots;
Route::get('/', function () {
    return view('welcome');
});

Route::get('/getData', [Hubspots::class,'getData'])->name('getData');
