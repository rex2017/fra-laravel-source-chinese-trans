<?php
/**
 * 路由，api
 */

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes 	API路由
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
| 这里你能注册你的应用API跻由。
| 这些路由被RouteServiceProvider加载使用分组，它包含api中间件组。
| 享受构建你的API！
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
