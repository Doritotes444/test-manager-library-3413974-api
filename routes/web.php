<?php
use Illuminate\Support\Facades\Route;
Route::get('/', fn() => response()->json(['app' => 'Manager Library API', 'status' => 'active']));