<?php

namespace Illuminate\Contracts\Http;

//定义契约的内核
interface Kernel
{
    /**
     * Bootstrap the application for HTTP requests.
	 * 引导应用为 HTTP 请求。
     *
     * @return void
     */
    public function bootstrap();

    /**
     * Handle an incoming HTTP request.
	 * 处理一个进入的 HTTP 请求。
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle($request);

    /**
     * Perform any final actions for the request lifecycle.
	 * 资源回收
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return void
     */
    public function terminate($request, $response);

    /**
     * Get the Laravel application instance.
	 * 获取应用接口
     *
     * @return \Illuminate\Contracts\Foundation\Application
     */
    public function getApplication();
}
