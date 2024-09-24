<?php
/**
 * 会话，开始会话
 */

namespace Illuminate\Session\Middleware;

use Closure;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

class StartSession
{
    /**
     * The session manager.
	 * 会话管理
     *
     * @var \Illuminate\Session\SessionManager
     */
    protected $manager;

    /**
     * Create a new session middleware.
	 * 创建新会话中间件
     *
     * @param  \Illuminate\Session\SessionManager  $manager
     * @return void
     */
    public function __construct(SessionManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Handle an incoming request.
	 * 处理传入请求
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (! $this->sessionConfigured()) {
            return $next($request);
        }

        // If a session driver has been configured, we will need to start the session here
        // so that the data is ready for an application. Note that the Laravel sessions
        // do not make use of PHP "native" sessions in any way since they are crappy.
		// 如果已配置会话驱动程序，则需要在此处启动会话，以便为应用程序准备好数据。
		// 请注意，Laravel会话不会以任何方式使用PHP"原生"会话，因为它们很糟糕。
        $request->setLaravelSession(
            $session = $this->startSession($request)
        );

        $this->collectGarbage($session);

        $response = $next($request);

        $this->storeCurrentUrl($request, $session);

        $this->addCookieToResponse($response, $session);

        // Again, if the session has been configured we will need to close out the session
        // so that the attributes may be persisted to some storage medium. We will also
        // add the session identifier cookie to the application response headers now.
		// 同样，如果会话已配置，我们将需要关闭会话，以便属性可以持久化到某些存储介质中。
		// 我们现在还将把会话标识符cookie添加到应用程序响应标头中。
        $this->saveSession($request);

        return $response;
    }

    /**
     * Start the session for the given request.
	 * 启动会话为给定请求
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\Session\Session
     */
    protected function startSession(Request $request)
    {
        return tap($this->getSession($request), function ($session) use ($request) {
            $session->setRequestOnHandler($request);

            $session->start();
        });
    }

    /**
     * Get the session implementation from the manager.
	 * 得到会话实现从管理器
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\Session\Session
     */
    public function getSession(Request $request)
    {
        return tap($this->manager->driver(), function ($session) use ($request) {
            $session->setId($request->cookies->get($session->getName()));
        });
    }

    /**
     * Remove the garbage from the session if necessary.
	 * 删除垃圾从会话中如果需要。
     *
     * @param  \Illuminate\Contracts\Session\Session  $session
     * @return void
     */
    protected function collectGarbage(Session $session)
    {
        $config = $this->manager->getSessionConfig();

        // Here we will see if this request hits the garbage collection lottery by hitting
        // the odds needed to perform garbage collection on any given request. If we do
        // hit it, we'll call this handler to let it delete all the expired sessions.
		// 在这里，我们将看到这个请求是否通过在任何给定的请求上执行垃圾收集所需的几率来命中垃圾收集彩票。
		// 如果我们确实点击了它，我们将调用此处理程序，让它删除所有过期的会话。
        if ($this->configHitsLottery($config)) {
            $session->getHandler()->gc($this->getSessionLifetimeInSeconds());
        }
    }

    /**
     * Determine if the configuration odds hit the lottery.
	 * 确定配置的概率是否命中彩票
     *
     * @param  array  $config
     * @return bool
     */
    protected function configHitsLottery(array $config)
    {
        return random_int(1, $config['lottery'][1]) <= $config['lottery'][0];
    }

    /**
     * Store the current URL for the request if necessary.
	 * 存储请求的当前URL如果需要
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Contracts\Session\Session  $session
     * @return void
     */
    protected function storeCurrentUrl(Request $request, $session)
    {
        if ($request->method() === 'GET' &&
            $request->route() &&
            ! $request->ajax() &&
            ! $request->prefetch()) {
            $session->setPreviousUrl($request->fullUrl());
        }
    }

    /**
     * Add the session cookie to the application response.
	 * 将会话cookie添加到应用程序响应中
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  \Illuminate\Contracts\Session\Session  $session
     * @return void
     */
    protected function addCookieToResponse(Response $response, Session $session)
    {
        if ($this->sessionIsPersistent($config = $this->manager->getSessionConfig())) {
            $response->headers->setCookie(new Cookie(
                $session->getName(), $session->getId(), $this->getCookieExpirationDate(),
                $config['path'], $config['domain'], $config['secure'] ?? false,
                $config['http_only'] ?? true, false, $config['same_site'] ?? null
            ));
        }
    }

    /**
     * Save the session data to storage.
	 * 保存会话数据至存储
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function saveSession($request)
    {
        $this->manager->driver()->save();
    }

    /**
     * Get the session lifetime in seconds.
	 * 得到会话生存期(以秒为单位)
     *
     * @return int
     */
    protected function getSessionLifetimeInSeconds()
    {
        return ($this->manager->getSessionConfig()['lifetime'] ?? null) * 60;
    }

    /**
     * Get the cookie lifetime in seconds.
	 * 得到会话生存期(以秒为单位)
     *
     * @return \DateTimeInterface|int
     */
    protected function getCookieExpirationDate()
    {
        $config = $this->manager->getSessionConfig();

        return $config['expire_on_close'] ? 0 : Date::instance(
            Carbon::now()->addRealMinutes($config['lifetime'])
        );
    }

    /**
     * Determine if a session driver has been configured.
	 * 确定是否已配置会话驱动程序
     *
     * @return bool
     */
    protected function sessionConfigured()
    {
        return ! is_null($this->manager->getSessionConfig()['driver'] ?? null);
    }

    /**
     * Determine if the configured session driver is persistent.
	 * 确定配置的会话驱动程序是否持久
     *
     * @param  array|null  $config
     * @return bool
     */
    protected function sessionIsPersistent(array $config = null)
    {
        $config = $config ?: $this->manager->getSessionConfig();

        return ! in_array($config['driver'], [null, 'array']);
    }
}
