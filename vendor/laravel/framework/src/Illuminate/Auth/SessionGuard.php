<?php
/**
 * 会话守卫
 */

namespace Illuminate\Auth;

use Illuminate\Auth\Events\Attempting;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\CurrentDeviceLogout;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\OtherDeviceLogout;
use Illuminate\Auth\Events\Validated;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Auth\SupportsBasicAuth;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Cookie\QueueingFactory as CookieJar;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class SessionGuard implements StatefulGuard, SupportsBasicAuth
{
    use GuardHelpers, Macroable;

    /**
     * The name of the Guard. Typically "session".
	 * 守卫的名字
     *
     * Corresponds to guard name in authentication configuration.
     *
     * @var string
     */
    protected $name;

    /**
     * The user we last attempted to retrieve.
	 * 我们上次尝试检索的用户
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable
     */
    protected $lastAttempted;

    /**
     * Indicates if the user was authenticated via a recaller cookie.
	 * 指明用户是否通过召回cookie进行身份验证
     *
     * @var bool
     */
    protected $viaRemember = false;

    /**
     * The session used by the guard.
	 * 守卫使用的会话
     *
     * @var \Illuminate\Contracts\Session\Session
     */
    protected $session;

    /**
     * The Illuminate cookie creator service.
	 * 照亮了会话创造者服务
     *
     * @var \Illuminate\Contracts\Cookie\QueueingFactory
     */
    protected $cookie;

    /**
     * The request instance.
	 * 请求实例
     *
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $request;

    /**
     * The event dispatcher instance.
	 * 事件调度程序实例
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * Indicates if the logout method has been called.
	 * 指明是否已调用注销方法
     *
     * @var bool
     */
    protected $loggedOut = false;

    /**
     * Indicates if a token user retrieval has been attempted.
	 * 指明是否尝试检索令牌用户
     *
     * @var bool
     */
    protected $recallAttempted = false;

    /**
     * Create a new authentication guard.
	 * 创建新的身份验证保护
     *
     * @param  string  $name
     * @param  \Illuminate\Contracts\Auth\UserProvider  $provider
     * @param  \Illuminate\Contracts\Session\Session  $session
     * @param  \Symfony\Component\HttpFoundation\Request|null  $request
     * @return void
     */
    public function __construct($name,
                                UserProvider $provider,
                                Session $session,
                                Request $request = null)
    {
        $this->name = $name;
        $this->session = $session;
        $this->request = $request;
        $this->provider = $provider;
    }

    /**
     * Get the currently authenticated user.
	 * 得到当前经过身份验证的用户
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {
        if ($this->loggedOut) {
            return;
        }

        // If we've already retrieved the user for the current request we can just
        // return it back immediately. We do not want to fetch the user data on
        // every call to this method because that would be tremendously slow.
		// 如果我们已经检索到当前请求的用户，我们可以立即将其返回。
		// 我们不想在每次调用此方法时都获取用户数据，因为这会非常慢。
        if (! is_null($this->user)) {
            return $this->user;
        }

        $id = $this->session->get($this->getName());

        // First we will try to load the user using the identifier in the session if
        // one exists. Otherwise we will check for a "remember me" cookie in this
        // request, and if one exists, attempt to retrieve the user using that.
		// 首先，如果会话中存在标识符，我们将尝试使用该标识符加载用户。
		// 否则，我们将在此请求中检查"记住我"cookie，如果存在，则尝试使用该cookie检索用户。
        if (! is_null($id) && $this->user = $this->provider->retrieveById($id)) {
            $this->fireAuthenticatedEvent($this->user);
        }

        // If the user is null, but we decrypt a "recaller" cookie we can attempt to
        // pull the user data on that cookie which serves as a remember cookie on
        // the application. Once we have a user we can return it to the caller.
		// 如果用户为空，但我们解密了一个"重述者"cookie，我们可以尝试在该cookie上提取用户数据，
		// 该cookie在应用程序上充当记忆cookie。一旦我们有了用户，我们就可以将其返回给调用者。
        if (is_null($this->user) && ! is_null($recaller = $this->recaller())) {
            $this->user = $this->userFromRecaller($recaller);

            if ($this->user) {
                $this->updateSession($this->user->getAuthIdentifier());

                $this->fireLoginEvent($this->user, true);
            }
        }

        return $this->user;
    }

    /**
     * Pull a user from the repository by its "remember me" cookie token.
	 * 通过"记住我"cookie令牌从存储库中拉出用户
     *
     * @param  \Illuminate\Auth\Recaller  $recaller
     * @return mixed
     */
    protected function userFromRecaller($recaller)
    {
        if (! $recaller->valid() || $this->recallAttempted) {
            return;
        }

        // If the user is null, but we decrypt a "recaller" cookie we can attempt to
        // pull the user data on that cookie which serves as a remember cookie on
        // the application. Once we have a user we can return it to the caller.
		// 如果用户为空，但我们解密了一个“重述者”cookie，我们可以尝试在该cookie上提取用户数据，
		// 该cookie在应用程序上充当记忆cookie。一旦我们有了用户，我们就可以将其返回给调用者。
        $this->recallAttempted = true;

        $this->viaRemember = ! is_null($user = $this->provider->retrieveByToken(
            $recaller->id(), $recaller->token()
        ));

        return $user;
    }

    /**
     * Get the decrypted recaller cookie for the request.
	 * 得到请求的解密的调用者cookie
     *
     * @return \Illuminate\Auth\Recaller|null
     */
    protected function recaller()
    {
        if (is_null($this->request)) {
            return;
        }

        if ($recaller = $this->request->cookies->get($this->getRecallerName())) {
            return new Recaller($recaller);
        }
    }

    /**
     * Get the ID for the currently authenticated user.
	 * 得到当前经过身份验证的用户的ID
     *
     * @return int|null
     */
    public function id()
    {
        if ($this->loggedOut) {
            return;
        }

        return $this->user()
                    ? $this->user()->getAuthIdentifier()
                    : $this->session->get($this->getName());
    }

    /**
     * Log a user into the application without sessions or cookies.
	 * 在没有会话或cookie的情况下将用户登录到应用程序
     *
     * @param  array  $credentials
     * @return bool
     */
    public function once(array $credentials = [])
    {
        $this->fireAttemptEvent($credentials);

        if ($this->validate($credentials)) {
            $this->setUser($this->lastAttempted);

            return true;
        }

        return false;
    }

    /**
     * Log the given user ID into the application without sessions or cookies.
	 * 将给定的用户ID登录到没有会话或cookie的应用程序中
     *
     * @param  mixed  $id
     * @return \Illuminate\Contracts\Auth\Authenticatable|false
     */
    public function onceUsingId($id)
    {
        if (! is_null($user = $this->provider->retrieveById($id))) {
            $this->setUser($user);

            return $user;
        }

        return false;
    }

    /**
     * Validate a user's credentials.
	 * 验证用户的凭据
     *
     * @param  array  $credentials
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        $this->lastAttempted = $user = $this->provider->retrieveByCredentials($credentials);

        return $this->hasValidCredentials($user, $credentials);
    }

    /**
     * Attempt to authenticate using HTTP Basic Auth.
	 * 尝试使用HTTP基本认证进行身份验证
     *
     * @param  string  $field
     * @param  array  $extraConditions
     * @return \Symfony\Component\HttpFoundation\Response|null
     */
    public function basic($field = 'email', $extraConditions = [])
    {
        if ($this->check()) {
            return;
        }

        // If a username is set on the HTTP basic request, we will return out without
        // interrupting the request lifecycle. Otherwise, we'll need to generate a
        // request indicating that the given credentials were invalid for login.
		// 如果在HTTP基本请求上设置了用户名，我们将在不中断请求生命周期的情况下返回。
		// 否则，我们需要生成一个请求，表明给定的凭据对于登录无效。
        if ($this->attemptBasic($this->getRequest(), $field, $extraConditions)) {
            return;
        }

        return $this->failedBasicResponse();
    }

    /**
     * Perform a stateless HTTP Basic login attempt.
	 * 执行无状态HTTP基本登录尝试
     *
     * @param  string  $field
     * @param  array  $extraConditions
     * @return \Symfony\Component\HttpFoundation\Response|null
     */
    public function onceBasic($field = 'email', $extraConditions = [])
    {
        $credentials = $this->basicCredentials($this->getRequest(), $field);

        if (! $this->once(array_merge($credentials, $extraConditions))) {
            return $this->failedBasicResponse();
        }
    }

    /**
     * Attempt to authenticate using basic authentication.
	 * 尝试使用基本身份验证进行身份验证
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @param  string  $field
     * @param  array  $extraConditions
     * @return bool
     */
    protected function attemptBasic(Request $request, $field, $extraConditions = [])
    {
        if (! $request->getUser()) {
            return false;
        }

        return $this->attempt(array_merge(
            $this->basicCredentials($request, $field), $extraConditions
        ));
    }

    /**
     * Get the credential array for a HTTP Basic request.
	 * 得到HTTP基本请求的凭据数组
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @param  string  $field
     * @return array
     */
    protected function basicCredentials(Request $request, $field)
    {
        return [$field => $request->getUser(), 'password' => $request->getPassword()];
    }

    /**
     * Get the response for basic authentication.
	 * 得到基本身份验证的响应
     *
     * @return void
     *
     * @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     */
    protected function failedBasicResponse()
    {
        throw new UnauthorizedHttpException('Basic', 'Invalid credentials.');
    }

    /**
     * Attempt to authenticate a user using the given credentials.
	 * 尝试使用给定凭据对用户进行身份验证
     *
     * @param  array  $credentials
     * @param  bool  $remember
     * @return bool
     */
    public function attempt(array $credentials = [], $remember = false)
    {
        $this->fireAttemptEvent($credentials, $remember);

        $this->lastAttempted = $user = $this->provider->retrieveByCredentials($credentials);

        // If an implementation of UserInterface was returned, we'll ask the provider
        // to validate the user against the given credentials, and if they are in
        // fact valid we'll log the users into the application and return true.
		// 如果返回了UserInterface的实现，我们将要求提供程序根据给定的凭据验证用户，
		// 如果它们确实有效，我们将用户登录到应用程序并返回true。
        if ($this->hasValidCredentials($user, $credentials)) {
            $this->login($user, $remember);

            return true;
        }

        // If the authentication attempt fails we will fire an event so that the user
        // may be notified of any suspicious attempts to access their account from
        // an unrecognized user. A developer may listen to this event as needed.
		// 如果身份验证尝试失败，我们将触发一个事件，
		// 以便用户可以收到来自未识别用户的任何可疑尝试访问其帐户的通知。
		// 开发人员可以根据需要收听此事件。
        $this->fireFailedEvent($user, $credentials);

        return false;
    }

    /**
     * Determine if the user matches the credentials.
	 * 确定用户是否与凭据匹配
     *
     * @param  mixed  $user
     * @param  array  $credentials
     * @return bool
     */
    protected function hasValidCredentials($user, $credentials)
    {
        $validated = ! is_null($user) && $this->provider->validateCredentials($user, $credentials);

        if ($validated) {
            $this->fireValidatedEvent($user);
        }

        return $validated;
    }

    /**
     * Log the given user ID into the application.
	 * 将给定的用户ID记录到应用程序中
     *
     * @param  mixed  $id
     * @param  bool  $remember
     * @return \Illuminate\Contracts\Auth\Authenticatable|false
     */
    public function loginUsingId($id, $remember = false)
    {
        if (! is_null($user = $this->provider->retrieveById($id))) {
            $this->login($user, $remember);

            return $user;
        }

        return false;
    }

    /**
     * Log a user into the application.
	 * 将用户登录到应用程序
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  bool  $remember
     * @return void
     */
    public function login(AuthenticatableContract $user, $remember = false)
    {
        $this->updateSession($user->getAuthIdentifier());

        // If the user should be permanently "remembered" by the application we will
        // queue a permanent cookie that contains the encrypted copy of the user
        // identifier. We will then decrypt this later to retrieve the users.
		// 如果应用程序应该永久"记住"用户，我们将排队一个永久cookie，其中包含用户标识符的加密副本。
		// 然后，我们稍后将对此进行解密以检索用户。
        if ($remember) {
            $this->ensureRememberTokenIsSet($user);

            $this->queueRecallerCookie($user);
        }

        // If we have an event dispatcher instance set we will fire an event so that
        // any listeners will hook into the authentication events and run actions
        // based on the login and logout events fired from the guard instances.
		// 如果我们设置了一个事件调度程序实例，我们将触发一个事件，
		// 这样任何侦听器都将挂接到身份验证事件中，并根据从保护实例触发的登录和注销事件运行操作。
        $this->fireLoginEvent($user, $remember);

        $this->setUser($user);
    }

    /**
     * Update the session with the given ID.
	 * 更新会话使用给定的ID
     *
     * @param  string  $id
     * @return void
     */
    protected function updateSession($id)
    {
        $this->session->put($this->getName(), $id);

        $this->session->migrate(true);
    }

    /**
     * Create a new "remember me" token for the user if one doesn't already exist.
	 * 创建一个新的"记住我”令牌(如果还不存在)为用户
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return void
     */
    protected function ensureRememberTokenIsSet(AuthenticatableContract $user)
    {
        if (empty($user->getRememberToken())) {
            $this->cycleRememberToken($user);
        }
    }

    /**
     * Queue the recaller cookie into the cookie jar.
	 * 将召回cookie排队放入cookie压缩包中
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return void
     */
    protected function queueRecallerCookie(AuthenticatableContract $user)
    {
        $this->getCookieJar()->queue($this->createRecaller(
            $user->getAuthIdentifier().'|'.$user->getRememberToken().'|'.$user->getAuthPassword()
        ));
    }

    /**
     * Create a "remember me" cookie for a given ID.
	 * 创建一个"记住我"cookie为给定的ID
     *
     * @param  string  $value
     * @return \Symfony\Component\HttpFoundation\Cookie
     */
    protected function createRecaller($value)
    {
        return $this->getCookieJar()->forever($this->getRecallerName(), $value);
    }

    /**
     * Log the user out of the application.
	 * 将用户从应用程序中注销
     *
     * @return void
     */
    public function logout()
    {
        $user = $this->user();

        $this->clearUserDataFromStorage();

        if (! is_null($this->user) && ! empty($user->getRememberToken())) {
            $this->cycleRememberToken($user);
        }

        // If we have an event dispatcher instance, we can fire off the logout event
        // so any further processing can be done. This allows the developer to be
        // listening for anytime a user signs out of this application manually.
		// 如果我们有一个事件调度器实例，我们可以触发注销事件，以便进行任何进一步的处理。
		// 这允许开发人员随时监听用户手动退出此应用程序。
        if (isset($this->events)) {
            $this->events->dispatch(new Logout($this->name, $user));
        }

        // Once we have fired the logout event we will clear the users out of memory
        // so they are no longer available as the user is no longer considered as
        // being signed into this application and should not be available here.
		// 一旦我们触发了注销事件，我们将清除内存中的用户，这样他们就不再可用，
		// 因为用户不再被视为已登录到此应用程序，因此不应在此处可用。
        $this->user = null;

        $this->loggedOut = true;
    }

    /**
     * Remove the user data from the session and cookies.
	 * 删除用户数据从会话和cookie中
     *
     * @return void
     */
    protected function clearUserDataFromStorage()
    {
        $this->session->remove($this->getName());

        if (! is_null($this->recaller())) {
            $this->getCookieJar()->queue($this->getCookieJar()
                    ->forget($this->getRecallerName()));
        }
    }

    /**
     * Refresh the "remember me" token for the user.
	 * 刷新用户的"记住我"令牌
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return void
     */
    protected function cycleRememberToken(AuthenticatableContract $user)
    {
        $user->setRememberToken($token = Str::random(60));

        $this->provider->updateRememberToken($user, $token);
    }

    /**
     * Log the user out of the application on their current device only.
	 * 仅在用户当前设备上注销其应用程序
     *
     * @return void
     */
    public function logoutCurrentDevice()
    {
        $user = $this->user();

        $this->clearUserDataFromStorage();

        // If we have an event dispatcher instance, we can fire off the logout event
        // so any further processing can be done. This allows the developer to be
        // listening for anytime a user signs out of this application manually.
		// 如果我们有一个事件调度器实例，我们可以触发注销事件，以便进行任何进一步的处理。
		// 这允许开发人员随时监听用户手动退出此应用程序。
        if (isset($this->events)) {
            $this->events->dispatch(new CurrentDeviceLogout($this->name, $user));
        }

        // Once we have fired the logout event we will clear the users out of memory
        // so they are no longer available as the user is no longer considered as
        // being signed into this application and should not be available here.
		// 一旦我们触发了注销事件，我们将清除内存中的用户，这样他们就不再可用，
		// 因为用户不再被视为已登录到此应用程序，因此不应在此处可用。
        $this->user = null;

        $this->loggedOut = true;
    }

    /**
     * Invalidate other sessions for the current user.
	 * 使当前用户的其他会话无效
     *
     * The application must be using the AuthenticateSession middleware.
     *
     * @param  string  $password
     * @param  string  $attribute
     * @return bool|null
     */
    public function logoutOtherDevices($password, $attribute = 'password')
    {
        if (! $this->user()) {
            return;
        }

        $result = tap($this->user()->forceFill([
            $attribute => Hash::make($password),
        ]))->save();

        if ($this->recaller() ||
            $this->getCookieJar()->hasQueued($this->getRecallerName())) {
            $this->queueRecallerCookie($this->user());
        }

        $this->fireOtherDeviceLogoutEvent($this->user());

        return $result;
    }

    /**
     * Register an authentication attempt event listener.
	 * 注册身份验证尝试事件侦听器
     *
     * @param  mixed  $callback
     * @return void
     */
    public function attempting($callback)
    {
        if (isset($this->events)) {
            $this->events->listen(Events\Attempting::class, $callback);
        }
    }

    /**
     * Fire the attempt event with the arguments.
	 * 触发尝试事件用参数
     *
     * @param  array  $credentials
     * @param  bool  $remember
     * @return void
     */
    protected function fireAttemptEvent(array $credentials, $remember = false)
    {
        if (isset($this->events)) {
            $this->events->dispatch(new Attempting(
                $this->name, $credentials, $remember
            ));
        }
    }

    /**
     * Fires the validated event if the dispatcher is set.
	 * 如果设置了调度程序，则触发已验证的事件。
     *
     * @param $user
     */
    protected function fireValidatedEvent($user)
    {
        if (isset($this->events)) {
            $this->events->dispatch(new Validated(
                $this->name, $user
            ));
        }
    }

    /**
     * Fire the login event if the dispatcher is set.
	 * 如果设置了调度程序，则触发登录事件。
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  bool  $remember
     * @return void
     */
    protected function fireLoginEvent($user, $remember = false)
    {
        if (isset($this->events)) {
            $this->events->dispatch(new Login(
                $this->name, $user, $remember
            ));
        }
    }

    /**
     * Fire the authenticated event if the dispatcher is set.
	 * 如果设置了调度程序，则触发经过身份验证的事件。
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return void
     */
    protected function fireAuthenticatedEvent($user)
    {
        if (isset($this->events)) {
            $this->events->dispatch(new Authenticated(
                $this->name, $user
            ));
        }
    }

    /**
     * Fire the other device logout event if the dispatcher is set.
	 * 如果设置了调度程序，则触发另一个设备注销事件。
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return void
     */
    protected function fireOtherDeviceLogoutEvent($user)
    {
        if (isset($this->events)) {
            $this->events->dispatch(new OtherDeviceLogout(
                $this->name, $user
            ));
        }
    }

    /**
     * Fire the failed authentication attempt event with the given arguments.
	 * 使用给定参数触发失败的身份验证尝试事件
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     * @param  array  $credentials
     * @return void
     */
    protected function fireFailedEvent($user, array $credentials)
    {
        if (isset($this->events)) {
            $this->events->dispatch(new Failed(
                $this->name, $user, $credentials
            ));
        }
    }

    /**
     * Get the last user we attempted to authenticate.
	 * 得到我们尝试验证的最后一个用户
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable
     */
    public function getLastAttempted()
    {
        return $this->lastAttempted;
    }

    /**
     * Get a unique identifier for the auth session value.
     *
     * @return string
     */
    public function getName()
    {
        return 'login_'.$this->name.'_'.sha1(static::class);
    }

    /**
     * Get the name of the cookie used to store the "recaller".
	 * 得到用于存储"召回器"的cookie的名称
     *
     * @return string
     */
    public function getRecallerName()
    {
        return 'remember_'.$this->name.'_'.sha1(static::class);
    }

    /**
     * Determine if the user was authenticated via "remember me" cookie.
	 * 确定用户是否通过"记住我"cookie进行了身份验证
     *
     * @return bool
     */
    public function viaRemember()
    {
        return $this->viaRemember;
    }

    /**
     * Get the cookie creator instance used by the guard.
	 * 得到守卫使用的cookie创建器实例
     *
     * @return \Illuminate\Contracts\Cookie\QueueingFactory
     *
     * @throws \RuntimeException
     */
    public function getCookieJar()
    {
        if (! isset($this->cookie)) {
            throw new RuntimeException('Cookie jar has not been set.');
        }

        return $this->cookie;
    }

    /**
     * Set the cookie creator instance used by the guard.
	 * 设置守卫使用的cookie创建器实例
     *
     * @param  \Illuminate\Contracts\Cookie\QueueingFactory  $cookie
     * @return void
     */
    public function setCookieJar(CookieJar $cookie)
    {
        $this->cookie = $cookie;
    }

    /**
     * Get the event dispatcher instance.
	 * 得到事件调度实例
     *
     * @return \Illuminate\Contracts\Events\Dispatcher
     */
    public function getDispatcher()
    {
        return $this->events;
    }

    /**
     * Set the event dispatcher instance.
	 * 设置事件调度实例
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function setDispatcher(Dispatcher $events)
    {
        $this->events = $events;
    }

    /**
     * Get the session store used by the guard.
     *
     * @return \Illuminate\Contracts\Session\Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Return the currently cached user.
	 * 返回当前已缓存的用户
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set the current user.
	 * 设置当前用户
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return $this
     */
    public function setUser(AuthenticatableContract $user)
    {
        $this->user = $user;

        $this->loggedOut = false;

        $this->fireAuthenticatedEvent($user);

        return $this;
    }

    /**
     * Get the current request instance.
	 * 得到当前请求实例
     *
     * @return \Symfony\Component\HttpFoundation\Request
     */
    public function getRequest()
    {
        return $this->request ?: Request::createFromGlobals();
    }

    /**
     * Set the current request instance.
	 * 设置当前请求实例
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return $this
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }
}
