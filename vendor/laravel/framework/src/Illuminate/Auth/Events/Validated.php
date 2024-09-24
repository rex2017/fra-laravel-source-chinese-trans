<?php
/**
 * 身份，经过验证的
 */

namespace Illuminate\Auth\Events;

use Illuminate\Queue\SerializesModels;

class Validated
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
     * The user retrieved and validated from the User Provider.
	 * 检索和验证用户从用户提供程序中
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
