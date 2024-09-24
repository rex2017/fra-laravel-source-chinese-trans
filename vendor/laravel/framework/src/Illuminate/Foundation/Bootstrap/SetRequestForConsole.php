<?php
/**
 * 基础，设置请求闭包
 */

namespace Illuminate\Foundation\Bootstrap;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;

class SetRequestForConsole
{
    /**
     * Bootstrap the given application.
	 * 引导给定应用
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        $uri = $app->make('config')->get('app.url', 'http://localhost');

        $components = parse_url($uri);

        $server = $_SERVER;

        if (isset($components['path'])) {
            $server = array_merge($server, [
                'SCRIPT_FILENAME' => $components['path'],
                'SCRIPT_NAME' => $components['path'],
            ]);
        }

        $app->instance('request', Request::create(
            $uri, 'GET', [], [], [], $server
        ));
    }
}
