<?php
/**
 * 基础，引导提供者
 */

namespace Illuminate\Foundation\Bootstrap;

use Illuminate\Contracts\Foundation\Application;

class BootProviders
{
    /**
     * Bootstrap the given application.
	 * 引导给定的应用
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        $app->boot();
    }
}
