<?php
/**
 * 验证服务提供者
 */

namespace Illuminate\Validation;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class ValidationServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register the service provider.
	 * 注册服务提供者
     *
     * @return void
     */
    public function register()
    {
        $this->registerPresenceVerifier();

        $this->registerValidationFactory();
    }

    /**
     * Register the validation factory.
	 * 注册验证工厂
     *
     * @return void
     */
    protected function registerValidationFactory()
    {
        $this->app->singleton('validator', function ($app) {
            $validator = new Factory($app['translator'], $app);

            // The validation presence verifier is responsible for determining the existence of
            // values in a given data collection which is typically a relational database or
            // other persistent data stores. It is used to check for "uniqueness" as well.
			// 验证存在验证器负责确定给定数据集合中是否存在值，
			// 该数据集合通常是关系数据库或其他持久数据存储。它也用于检查"唯一性"。
            if (isset($app['db'], $app['validation.presence'])) {
                $validator->setPresenceVerifier($app['validation.presence']);
            }

            return $validator;
        });
    }

    /**
     * Register the database presence verifier.
	 * 注册数据库状态验证器
     *
     * @return void
     */
    protected function registerPresenceVerifier()
    {
        $this->app->singleton('validation.presence', function ($app) {
            return new DatabasePresenceVerifier($app['db']);
        });
    }

    /**
     * Get the services provided by the provider.
	 * 得到提供者提供的服务
     *
     * @return array
     */
    public function provides()
    {
        return [
            'validator', 'validation.presence',
        ];
    }
}
