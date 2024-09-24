<?php
/**
 * 基础，注册提供者
 */

namespace Illuminate\Foundation\Bootstrap;

use Illuminate\Contracts\Foundation\Application;

class RegisterProviders
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
        $app->registerConfiguredProviders();
    }
}
