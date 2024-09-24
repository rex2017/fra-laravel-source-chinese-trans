<?php
/**
 * 身份，登录
 */

namespace Illuminate\Auth\Events;

use Illuminate\Queue\SerializesModels;

class Login
{
    use SerializesModels;

    /**
     * The authentication guard name.
	 * 认证守卫名称
     *
     * @var string
     */
    public $guard;

    /**
     * The authenticated user.
	 * 通过身份验证的用户
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable
     */
    public $user;

    /**
     * Indicates if the user should be "remembered".
	 * 指明是否需要"记住我"
     *
     * @var bool
     */
    public $remember;

    /**
     * Create a new event instance.
	 * 创建新的事件实例
     *
     * @param  string  $guard
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  bool  $remember
     * @return void
     */
    public function __construct($guard, $user, $remember)
    {
        $this->user = $user;
        $this->guard = $guard;
        $this->remember = $remember;
    }
}
