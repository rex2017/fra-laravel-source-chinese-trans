<?php
/**
 * web路由
 */

/*
|--------------------------------------------------------------------------
| Web Routes 	Web路由
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
| 在这里我们可以注册你应用里的web路由。
| 这些路由被RouteServiceProvider加载使用分组，它包含web中间件组。
| 现在创造伟大的东西!
|
*/

Route::get('/', function () {
    return view('welcome');
});

//自定义添加
Route::get('/index', 'IndexController@index');
Route::get('/test', 'TestController@test');
Route::get('/test/{func}', 'TestController@func');
