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
 *
 * index.php 文件载入 Composer 生成的自动加载设置，
 * 然后从 bootstrap/app.php 脚本获取 Laravel应用实例，
 * Laravel的第一个动作就是创建服务容器实例。
 *
 * 接下来，请求被发送到 HTTP 内核或 Console内核，这取决于进入应用的请求类型。
 * 这两个内核是所有请求要经过的中央处理器。
 * 还定义了一系列所有请求在处理前需要经过的HTTP中间件，这些中间件处理HTTP会话的
 * 读写、判断应用是否处于维护模式、验证 CSRF 令牌等等。
 *
 * HTTP内核的 handle 方法签名比较简单：获取一个 Request, 返回一个 Response，
 * 可以把该内核想象作一个代表整个应用的大黑盒子，输入 HTTP 请求，返回 HTTP 响应。
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

//step1 注册加载composer自动生成的class loader就是加载初始化第三方依赖，不属于laravel核心，到此为止
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
| 一旦生成应用，我们能控制进来的应用通用 kernel，发送显示响应给客户端浏览器
| 允许他们去创建美好的应用
*/

//step2 生成容器 Container，并向容器注册核心组件，这里牵涉到了容器 Container 和契约 Contracts，
//这是laravel的重点
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

//step3 处理请求，生成发送响应。
//首先laravel框架捕获到用户发到 index.php 的请求，生成 Illuminate\Http\Request 实例，
//传递给这个小小的 handle 方法。在方法内部，将该 $request 实例绑定到第二步生成的 $app 容器上。
//然后在该请求真正处理之前，调用 bootstrap 方法，进行必要的加载和注册，如检测环境，加载配置
//注册 Facades（假象），注册服务提供者，启动服务提供者等等。
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$response->send();

//step4 请求结束，进行回调
$kernel->terminate($request, $response);
