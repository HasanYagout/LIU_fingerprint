<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/test', function () {
    dd(\App\Models\TUser::all(),\App\Models\User::all());
});
