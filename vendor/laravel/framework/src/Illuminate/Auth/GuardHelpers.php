<?php
/**
 * 守卫助手
 */

namespace Illuminate\Auth;

use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\UserProvider;

/**
 * These methods are typically the same across all guards.
 * 这些方法在所有守卫中通常是相同的
 */
trait GuardHelpers
{
    /**
     * The currently authenticated user.
	 * 当前认证的用户
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable
     */
    protected $user;

    /**
     * The user provider implementation.
	 * 用户提供程序实现
     *
     * @var \Illuminate\Contracts\Auth\UserProvider
     */
    protected $provider;

    /**
     * Determine if current user is authenticated. If not, throw an exception.
	 * 确定当前用户是否经过身份验证。如果不是，则抛出异常。
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function authenticate()
    {
        if (! is_null($user = $this->user())) {
            return $user;
        }

        throw new AuthenticationException;
    }

    /**
     * Determine if the guard has a user instance.
	 * 确定守卫是否有用户实例
     *
     * @return bool
     */
    public function hasUser()
    {
        return ! is_null($this->user);
    }

    /**
     * Determine if the current user is authenticated.
	 * 确定当前用户是否经过身份验证
     *
     * @return bool
     */
    public function check()
    {
        return ! is_null($this->user());
    }

    /**
     * Determine if the current user is a guest.
	 * 确定当前用户是否是访客
     *
     * @return bool
     */
    public function guest()
    {
        return ! $this->check();
    }

    /**
     * Get the ID for the currently authenticated user.
	 * 得到当前经过身份验证的用户的ID
     *
     * @return int|null
     */
    public function id()
    {
        if ($this->user()) {
            return $this->user()->getAuthIdentifier();
        }
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

        return $this;
    }

    /**
     * Get the user provider used by the guard.
	 * 得到守卫使用的用户提供者
     *
     * @return \Illuminate\Contracts\Auth\UserProvider
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * Set the user provider used by the guard.
	 * 设置守卫使用的用户提供者
     *
     * @param  \Illuminate\Contracts\Auth\UserProvider  $provider
     * @return void
     */
    public function setProvider(UserProvider $provider)
    {
        $this->provider = $provider;
    }
}
