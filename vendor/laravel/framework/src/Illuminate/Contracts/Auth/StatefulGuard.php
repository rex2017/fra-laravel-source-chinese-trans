<?php
/**
 * 契约，有状态的守卫
 */

namespace Illuminate\Contracts\Auth;

interface StatefulGuard extends Guard
{
    /**
     * Attempt to authenticate a user using the given credentials.
	 * 尝试使用给定凭据对用户进行身份验证
     *
     * @param  array  $credentials
     * @param  bool  $remember
     * @return bool
     */
    public function attempt(array $credentials = [], $remember = false);

    /**
     * Log a user into the application without sessions or cookies.
	 * 在没有会话或cookie的情况下将用户登录到应用程序
     *
     * @param  array  $credentials
     * @return bool
     */
    public function once(array $credentials = []);

    /**
     * Log a user into the application.
	 * 将用户登录到应用程序
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  bool  $remember
     * @return void
     */
    public function login(Authenticatable $user, $remember = false);

    /**
     * Log the given user ID into the application.
	 * 将给定的用户ID记录到应用程序中
     *
     * @param  mixed  $id
     * @param  bool  $remember
     * @return \Illuminate\Contracts\Auth\Authenticatable
     */
    public function loginUsingId($id, $remember = false);

    /**
     * Log the given user ID into the application without sessions or cookies.
	 * 将给定的用户ID登录到没有会话或cookie的应用程序中
     *
     * @param  mixed  $id
     * @return bool
     */
    public function onceUsingId($id);

    /**
     * Determine if the user was authenticated via "remember me" cookie.
	 * 确定用户是否通过“记住我”cookie进行了身份验证
     *
     * @return bool
     */
    public function viaRemember();

    /**
     * Log the user out of the application.
	 * 将用户从应用程序中注销
     *
     * @return void
     */
    public function logout();
}
