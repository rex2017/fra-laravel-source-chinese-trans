<?php
/**
 * 身份，尝试
 */

namespace Illuminate\Auth\Events;

class Attempting
{
    /**
     * The authentication guard name.
	 * 认证守卫名称
     *
     * @var string
     */
    public $guard;

    /**
     * The credentials for the user.
	 * 用户的凭据
     *
     * @var array
     */
    public $credentials;

    /**
     * Indicates if the user should be "remembered".
	 * 指明是否需要"记住我*
     * @var bool
     */
    public $remember;

    /**
     * Create a new event instance.
	 * 创建新的事件实例
     *
     * @param  string  $guard
     * @param  array  $credentials
     * @param  bool  $remember
     * @return void
     */
    public function __construct($guard, $credentials, $remember)
    {
        $this->guard = $guard;
        $this->remember = $remember;
        $this->credentials = $credentials;
    }
}
