<?php
/**
 * 身份，失败
 */

namespace Illuminate\Auth\Events;

class Failed
{
    /**
     * The authentication guard name.
	 * 认证守卫名称
     *
     * @var string
     */
    public $guard;

    /**
     * The user the attempter was trying to authenticate as.
	 * 尝试器试图验证的用户
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public $user;

    /**
     * The credentials provided by the attempter.
	 * 由尝试器提供的凭证
     *
     * @var array
     */
    public $credentials;

    /**
     * Create a new event instance.
	 * 创建新的事件实例
     *
     * @param  string  $guard
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     * @param  array  $credentials
     * @return void
     */
    public function __construct($guard, $user, $credentials)
    {
        $this->user = $user;
        $this->guard = $guard;
        $this->credentials = $credentials;
    }
}
