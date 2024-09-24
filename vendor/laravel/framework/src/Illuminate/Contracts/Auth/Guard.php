<?php
/**
 * 契约，认证守卫接口
 */

namespace Illuminate\Contracts\Auth;

interface Guard
{
    /**
     * Determine if the current user is authenticated.
	 * 确定当前用户是否经过身份验证
     *
     * @return bool
     */
    public function check();

    /**
     * Determine if the current user is a guest.
	 * 确定当前用户为访客
     *
     * @return bool
     */
    public function guest();

    /**
     * Get the currently authenticated user.
	 * 得到当前经过身份验证的用户
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user();

    /**
     * Get the ID for the currently authenticated user.
	 * 得到当前经过身份验证的用户的ID
     *
     * @return int|string|null
     */
    public function id();

    /**
     * Validate a user's credentials.
	 * 验证用户的凭据
     *
     * @param  array  $credentials
     * @return bool
     */
    public function validate(array $credentials = []);

    /**
     * Set the current user.
	 * 设置当前用户
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return void
     */
    public function setUser(Authenticatable $user);
}
