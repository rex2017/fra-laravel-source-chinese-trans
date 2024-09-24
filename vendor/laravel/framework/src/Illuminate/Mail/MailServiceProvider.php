<?php
/**
 * 邮件服务提供者
 */

namespace Illuminate\Mail;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Swift_DependencyContainer;
use Swift_Mailer;

class MailServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register the service provider.
	 * 注册服务提供者
     *
     * @return void
     */
    public function register()
    {
        $this->registerSwiftMailer();
        $this->registerIlluminateMailer();
        $this->registerMarkdownRenderer();
    }

    /**
     * Register the Illuminate mailer instance.
	 * 注册点亮邮件实例
     *
     * @return void
     */
    protected function registerIlluminateMailer()
    {
        $this->app->singleton('mailer', function ($app) {
            $config = $app->make('config')->get('mail');

            // Once we have create the mailer instance, we will set a container instance
            // on the mailer. This allows us to resolve mailer classes via containers
            // for maximum testability on said classes instead of passing Closures.
			// 创建邮件程序实例后，我们将在邮件程序上设置一个容器实例。
			// 这允许我们通过容器解析邮件程序类，以在所述类上实现最大的可测试性，而不是传递闭包。
            $mailer = new Mailer(
                $app['view'], $app['swift.mailer'], $app['events']
            );

            if ($app->bound('queue')) {
                $mailer->setQueue($app['queue']);
            }

            // Next we will set all of the global addresses on this mailer, which allows
            // for easy unification of all "from" addresses as well as easy debugging
            // of sent messages since they get be sent into a single email address.
			// 接下来，我们将在此邮件程序上设置所有全局地址，这允许轻松统一所有"发件人"地址，
			// 以及轻松调试发送的消息，因为它们被发送到一个电子邮件地址。
            foreach (['from', 'reply_to', 'to'] as $type) {
                $this->setGlobalAddress($mailer, $config, $type);
            }

            return $mailer;
        });
    }

    /**
     * Set a global address on the mailer by type.
	 * 设置全局地址在邮件上按类型
     *
     * @param  \Illuminate\Mail\Mailer  $mailer
     * @param  array  $config
     * @param  string  $type
     * @return void
     */
    protected function setGlobalAddress($mailer, array $config, $type)
    {
        $address = Arr::get($config, $type);

        if (is_array($address) && isset($address['address'])) {
            $mailer->{'always'.Str::studly($type)}($address['address'], $address['name']);
        }
    }

    /**
     * Register the Swift Mailer instance.
	 * 注册Swift Mailer实例
     *
     * @return void
     */
    public function registerSwiftMailer()
    {
        $this->registerSwiftTransport();

        // Once we have the transporter registered, we will register the actual Swift
        // mailer instance, passing in the transport instances, which allows us to
        // override this transporter instances during app start-up if necessary.
		// 一旦我们注册了运输器，我们将注册实际的Swift邮件程序实例，传入运输实例，
		// 这允许我们在必要时在应用程序启动期间覆盖此运输器实例。
        $this->app->singleton('swift.mailer', function ($app) {
            if ($domain = $app->make('config')->get('mail.domain')) {
                Swift_DependencyContainer::getInstance()
                                ->register('mime.idgenerator.idright')
                                ->asValue($domain);
            }

            return new Swift_Mailer($app['swift.transport']->driver());
        });
    }

    /**
     * Register the Swift Transport instance.
	 * 注册Swift Transport实例
     *
     * @return void
     */
    protected function registerSwiftTransport()
    {
        $this->app->singleton('swift.transport', function ($app) {
            return new TransportManager($app);
        });
    }

    /**
     * Register the Markdown renderer instance.
	 * 注册Markdown渲染器实例
     *
     * @return void
     */
    protected function registerMarkdownRenderer()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/resources/views' => $this->app->resourcePath('views/vendor/mail'),
            ], 'laravel-mail');
        }

        $this->app->singleton(Markdown::class, function ($app) {
            $config = $app->make('config');

            return new Markdown($app->make('view'), [
                'theme' => $config->get('mail.markdown.theme', 'default'),
                'paths' => $config->get('mail.markdown.paths', []),
            ]);
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
            'mailer', 'swift.mailer', 'swift.transport', Markdown::class,
        ];
    }
}
