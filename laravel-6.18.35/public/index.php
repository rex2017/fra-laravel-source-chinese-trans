<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * @package  Laravel
 * @author   Taylor Otwell <taylor@laravel.com>
 */

define('LARAVEL_START', microtime(true));

/**
 * 这是应用的所有请求入口，所有请求都会补web服务器导向这个文件。
 * index.php 文件包含的代码并不多，但是，这里是加载框架其他部分的起点。
 *
 * index.php 文件载入 Composer 生成的自动加载设置，然后从 bootstrap/app.php 脚本获取 Laravel应用实例，
 * Laravel的第一个动作就是创建服务容器实例。
 *
 * 接下来，请求被发送到 HTTP 内核或 Console内核，这取决于进入应用的请求类型。这两个内核是所有请求要经过
 * 的中央处理器。
 * 还定义了一系列所有请求在处理前需要经过的HTTP中间件，这些中间件处理HTTP会话的读写、判断应用是否处于
 * 维护模式、验证 CSRF 令牌等等。
 *
 * HTTP内核的 handle 方法签名比较简单：获取一个 Request, 返回一个 Response，可以把该内核想象作一个代表整
 * 个应用的大黑盒子，输入 HTTP 请求，返回 HTTP 响应。
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
| 我们需要点亮 PHP开发，所以我们需要亮灯。
| bootstrap下面的类将准备使用，它将加载这个应用使我们能运行，返回显示内容
| 到浏览器并使用户愉悦。
|
*/

$app = require_once __DIR__.'/../bootstrap/app.php';

//var_dump($app);exit;

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
*/

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$response->send();

$kernel->terminate($request, $response);
