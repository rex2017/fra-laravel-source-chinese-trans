<?php
/**
 * 基础，与身份验证交互
 */

namespace Illuminate\Foundation\Testing\Concerns;

use Illuminate\Contracts\Auth\Authenticatable as UserContract;

trait InteractsWithAuthentication
{
    /**
     * Set the currently logged in user for the application.
	 * 设置当前登录的用户为应用
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string|null  $driver
     * @return $this
     */
    public function actingAs(UserContract $user, $driver = null)
    {
        return $this->be($user, $driver);
    }

    /**
     * Set the currently logged in user for the application.
	 * 设置当前登录的用户为应用
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string|null  $driver
     * @return $this
     */
    public function be(UserContract $user, $driver = null)
    {
        if (isset($user->wasRecentlyCreated) && $user->wasRecentlyCreated) {
            $user->wasRecentlyCreated = false;
        }

        $this->app['auth']->guard($driver)->setUser($user);

        $this->app['auth']->shouldUse($driver);

        return $this;
    }

    /**
     * Assert that the user is authenticated.
	 * 断言用户已经过身份验证
     *
     * @param  string|null  $guard
     * @return $this
     */
    public function assertAuthenticated($guard = null)
    {
        $this->assertTrue($this->isAuthenticated($guard), 'The user is not authenticated');

        return $this;
    }

    /**
     * Assert that the user is not authenticated.
	 * 断言用户没身份验证
     *
     * @param  string|null  $guard
     * @return $this
     */
    public function assertGuest($guard = null)
    {
        $this->assertFalse($this->isAuthenticated($guard), 'The user is authenticated');

        return $this;
    }

    /**
     * Return true if the user is authenticated, false otherwise.
	 * 返回真如果用户已被身份验证
     *
     * @param  string|null  $guard
     * @return bool
     */
    protected function isAuthenticated($guard = null)
    {
        return $this->app->make('auth')->guard($guard)->check();
    }

    /**
     * Assert that the user is authenticated as the given user.
	 * 断言用户被验证为给定的用户
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string|null  $guard
     * @return $this
     */
    public function assertAuthenticatedAs($user, $guard = null)
    {
        $expected = $this->app->make('auth')->guard($guard)->user();

        $this->assertNotNull($expected, 'The current user is not authenticated.');

        $this->assertInstanceOf(
            get_class($expected), $user,
            'The currently authenticated user is not who was expected'
        );

        $this->assertSame(
            $expected->getAuthIdentifier(), $user->getAuthIdentifier(),
            'The currently authenticated user is not who was expected'
        );

        return $this;
    }

    /**
     * Assert that the given credentials are valid.
	 * 断言给定的凭证是有效的
     *
     * @param  array  $credentials
     * @param  string|null  $guard
     * @return $this
     */
    public function assertCredentials(array $credentials, $guard = null)
    {
        $this->assertTrue(
            $this->hasCredentials($credentials, $guard), 'The given credentials are invalid.'
        );

        return $this;
    }

    /**
     * Assert that the given credentials are invalid.
	 * 断言给定的凭证是无效的
     *
     * @param  array  $credentials
     * @param  string|null  $guard
     * @return $this
     */
    public function assertInvalidCredentials(array $credentials, $guard = null)
    {
        $this->assertFalse(
            $this->hasCredentials($credentials, $guard), 'The given credentials are valid.'
        );

        return $this;
    }

    /**
     * Return true if the credentials are valid, false otherwise.
	 * 返回true如果凭据有效，否则返回false
     *
     * @param  array  $credentials
     * @param  string|null  $guard
     * @return bool
     */
    protected function hasCredentials(array $credentials, $guard = null)
    {
        $provider = $this->app->make('auth')->guard($guard)->getProvider();

        $user = $provider->retrieveByCredentials($credentials);

        return $user && $provider->validateCredentials($user, $credentials);
    }
}
