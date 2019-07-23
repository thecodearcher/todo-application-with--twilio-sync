<?php

use Illuminate\Http\Request;

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

Route::get('/todo', "TodoController@getTodo");
Route::post('/todo', "TodoController@createTodo");
Route::put('/todo/{sid}', "TodoController@updateTodo");
Route::delete('/todo/{sid}', "TodoController@deleteTodo");
