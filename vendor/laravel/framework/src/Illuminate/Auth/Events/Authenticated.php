<?php
/**
 * 身份，已验证
 */

namespace Illuminate\Auth\Events;

use Illuminate\Queue\SerializesModels;

class Authenticated
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
	 * 已通过验证用户
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable
     */
    public $user;

    /**
     * Create a new event instance.
	 * 创建新的事件实例
     *
     * @param  string  $guard
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return void
     */
    public function __construct($guard, $user)
    {
        $this->user = $user;
        $this->guard = $guard;
    }
}
