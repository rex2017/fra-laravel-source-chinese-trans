<?php
/**
 * 翻译服务提供商
 */

namespace Illuminate\Translation;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class TranslationServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register the service provider.
	 * 注册服务提供者
     *
     * @return void
     */
    public function register()
    {
        $this->registerLoader();

        $this->app->singleton('translator', function ($app) {
            $loader = $app['translation.loader'];

            // When registering the translator component, we'll need to set the default
            // locale as well as the fallback locale. So, we'll grab the application
            // configuration so we can easily get both of these values from there.
			// 注册翻译器组件时，我们需要设置默认区域设置和回退区域设置。
			// 因此，我们将获取应用程序配置，以便从那里轻松获取这两个值。
            $locale = $app['config']['app.locale'];

            $trans = new Translator($loader, $locale);

            $trans->setFallback($app['config']['app.fallback_locale']);

            return $trans;
        });
    }

    /**
     * Register the translation line loader.
	 * 注册翻译行加载程序
     *
     * @return void
     */
    protected function registerLoader()
    {
        $this->app->singleton('translation.loader', function ($app) {
            return new FileLoader($app['files'], $app['path.lang']);
        });
    }

    /**
     * Get the services provided by the provider.
	 * 得到服务提供者
     *
     * @return array
     */
    public function provides()
    {
        return ['translator', 'translation.loader'];
    }
}
