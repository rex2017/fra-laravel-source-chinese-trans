<?php
/**
 * 日志服务提供者
 */

namespace Illuminate\Log;

use Illuminate\Support\ServiceProvider;

class LogServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
	 * 注册服务提供者，返回LogManager
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('log', function () {
            return new LogManager($this->app);
        });
    }
}
