<?php

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

Route::get('/', function () {
    return view('welcome');
});

//以下为新增的 router

//Route::resource('user'=>'UserController');
//Route::resource('user', 'UserController');
//Route::get($uri, $callback);

Route::get('/index', 'IndexController@index');

Route::get('/test', 'TestController@index');
