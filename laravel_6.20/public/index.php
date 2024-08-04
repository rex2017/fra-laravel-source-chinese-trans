<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * @package  Laravel
 * @author   Taylor Otwell <taylor@laravel.com>
 */

define('LARAVEL_START', microtime(true));

/**
 * 这是应用的所有请求入口，所有请求都会进web服务器导向这个文件。
 * index.php 文件包含的代码并不多，但是，这里是加载框架其他部分的起点。
 */
 
/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| our application. We just need to utilize it! We'll simply require it
| into the script here so that we don't have to worry about manual
| loading any of our classes later on. It feels great to relax.
| 自动加载 vendor 目录
|
*/

require __DIR__.'/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Turn On The Lights
|--------------------------------------------------------------------------
|
| We need to illuminate PHP development, so let us turn on the lights.
| This bootstraps the framework and gets it ready for use, then it
| will load up this application so that we can run it and send
| the responses back to the browser and delight our users.
| 
| 我们需要点亮PHP开发，所以我们需要亮灯。
| bootstrap下面的类将准备使用，它将加载这个应用使我们能运行，返回显示内容
| 到浏览器并使用户高兴。
|
*/

//step1 注册加载composer自动生成的class loader就是加载初始化第三方依赖并创建app实例
//这里同时绑定了核心，web、命令行、异常
$app = require_once __DIR__.'/../bootstrap/app.php';

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request
| through the kernel, and send the associated response back to
| the client's browser allowing them to enjoy the creative
| and wonderful application we have prepared for them.
| 
| 一旦生成应用，我们能控制进来的应用通过kernel，并发送显示响应给客户端浏览器
| 允许他们去创建美好的应用
|
*/

//step2 Kernel内核实例化
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

//step3 处理请求，生成发送响应
//Illuminate\Foundation\Http\Kernel
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$response->send();

//step4 请求结束，进行回调
$kernel->terminate($request, $response);
