<?php
/**
 * 基础，授权服务提供者
 */

namespace Illuminate\Foundation\Support\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
	 * 应用程序的策略映射
     *
     * @var array
     */
    protected $policies = [];

    /**
     * Register the application's policies.
	 * 注册应用程序的策略
     *
     * @return void
     */
    public function registerPolicies()
    {
        foreach ($this->policies() as $key => $value) {
            Gate::policy($key, $value);
        }
    }

    /**
     * Get the policies defined on the provider.
	 * 得到在提供程序上定义的策略
     *
     * @return array
     */
    public function policies()
    {
        return $this->policies;
    }
}
