<?php
/**
 * 路由服务提供者
 */

namespace Illuminate\Routing;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Routing\ResponseFactory as ResponseFactoryContract;
use Illuminate\Contracts\Routing\UrlGenerator as UrlGeneratorContract;
use Illuminate\Contracts\View\Factory as ViewFactoryContract;
use Illuminate\Routing\Contracts\ControllerDispatcher as ControllerDispatcherContract;
use Illuminate\Support\ServiceProvider;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response as NyholmPsrResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Zend\Diactoros\Response as ZendPsrResponse;
use Zend\Diactoros\ServerRequestFactory;

class RoutingServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
	 * 注册服务提供者
     *
     * @return void
     */
    public function register()
    {
        $this->registerRouter();			#router放容器的bindings里
        $this->registerUrlGenerator();		#url放容器的bindings里
        $this->registerRedirector();		#redirect放容器的bindings里
        $this->registerPsrRequest();		#ServerRequestInterface放容器的bindings里
        $this->registerPsrResponse();		#ResponseInterface放容器的bindings里
        $this->registerResponseFactory();	#ResponseFactoryContract放容器的bindings里
        $this->registerControllerDispatcher();  #ControllerDispatcherContract放容器的bindings里
    }

    /**
     * Register the router instance.
	 * 注册路由实例，router
     *
     * @return void
     */
    protected function registerRouter()
    {
        $this->app->singleton('router', function ($app) {
            return new Router($app['events'], $app);
        });
    }

    /**
     * Register the URL generator service.
	 * 注册URL生成器服务，url
     *
     * @return void
     */
    protected function registerUrlGenerator()
    {
        $this->app->singleton('url', function ($app) {
            $routes = $app['router']->getRoutes();

            // The URL generator needs the route collection that exists on the router.
            // Keep in mind this is an object, so we're passing by references here
            // and all the registered routes will be available to the generator.
			// URL生成器需要路由器上存在的路由集合。
			// 请记住，这是一个对象，因此我们在这里传递引用，所有注册的路由都将可供生成器使用。
            $app->instance('routes', $routes);

            return new UrlGenerator(
                $routes, $app->rebinding(
                    'request', $this->requestRebinder()
                ), $app['config']['app.asset_url']
            );
        });

        $this->app->extend('url', function (UrlGeneratorContract $url, $app) {
            // Next we will set a few service resolvers on the URL generator so it can
            // get the information it needs to function. This just provides some of
            // the convenience features to this URL generator like "signed" URLs.
			// 接下来，我们将在URL生成器上设置一些服务解析器，以便它能够获取运行所需的信息。
			// 这只是为这个URL生成器提供了一些便利功能，如"签名"URL。
            $url->setSessionResolver(function () {
                return $this->app['session'] ?? null;
            });

            $url->setKeyResolver(function () {
                return $this->app->make('config')->get('app.key');
            });

            // If the route collection is "rebound", for example, when the routes stay
            // cached for the application, we will need to rebind the routes on the
            // URL generator instance so it has the latest version of the routes.
			// 例如，如果路由集合是"反弹"的，当路由为应用程序保持缓存时，
			// 我们需要在URL生成器实例上重新绑定路由，使其具有最新版本的路由。
            $app->rebinding('routes', function ($app, $routes) {
                $app['url']->setRoutes($routes);
            });

            return $url;
        });
    }

    /**
     * Get the URL generator request rebinder.
	 * 得到URL生成器请求重新绑定器
     *
     * @return \Closure
     */
    protected function requestRebinder()
    {
        return function ($app, $request) {
            $app['url']->setRequest($request);
        };
    }

    /**
     * Register the Redirector service.
	 * 注册重定向服务，redirect
     *
     * @return void
     */
    protected function registerRedirector()
    {
        $this->app->singleton('redirect', function ($app) {
            $redirector = new Redirector($app['url']);

            // If the session is set on the application instance, we'll inject it into
            // the redirector instance. This allows the redirect responses to allow
            // for the quite convenient "with" methods that flash to the session.
			// 如果会话是在应用程序实例上设置的，我们将把它注入重定向器实例。
			// 这允许重定向响应允许非常方便的“with”方法快速跳转到会话。
            if (isset($app['session.store'])) {
                $redirector->setSession($app['session.store']);
            }

            return $redirector;
        });
    }

    /**
     * Register a binding for the PSR-7 request implementation.
	 * 注册绑定为PSR-7请求实现
     *
     * @return void
     */
    protected function registerPsrRequest()
    {
        $this->app->bind(ServerRequestInterface::class, function ($app) {
            if (class_exists(Psr17Factory::class) && class_exists(PsrHttpFactory::class)) {
                $psr17Factory = new Psr17Factory;

                return (new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory))
                    ->createRequest($app->make('request'));
            }

            if (class_exists(ServerRequestFactory::class) && class_exists(DiactorosFactory::class)) {
                return (new DiactorosFactory)->createRequest($app->make('request'));
            }

            throw new BindingResolutionException('Unable to resolve PSR request. Please install the symfony/psr-http-message-bridge and nyholm/psr7 packages.');
        });
    }

    /**
     * Register a binding for the PSR-7 response implementation.
	 * 注册绑定为PSR-7响应实现
     *
     * @return void
     */
    protected function registerPsrResponse()
    {
        $this->app->bind(ResponseInterface::class, function () {
            if (class_exists(NyholmPsrResponse::class)) {
                return new NyholmPsrResponse;
            }

            if (class_exists(ZendPsrResponse::class)) {
                return new ZendPsrResponse;
            }

            throw new BindingResolutionException('Unable to resolve PSR response. Please install the nyholm/psr7 package.');
        });
    }

    /**
     * Register the response factory implementation.
	 * 注册响应工厂实现
     *
     * @return void
     */
    protected function registerResponseFactory()
    {
        $this->app->singleton(ResponseFactoryContract::class, function ($app) {
            return new ResponseFactory($app[ViewFactoryContract::class], $app['redirect']);
        });
    }

    /**
     * Register the controller dispatcher.
	 * 注册控制器调度
     *
     * @return void
     */
    protected function registerControllerDispatcher()
    {
        $this->app->singleton(ControllerDispatcherContract::class, function ($app) {
            return new ControllerDispatcher($app);
        });
    }
}
