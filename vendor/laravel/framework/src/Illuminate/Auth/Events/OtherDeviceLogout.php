<?php
/**
 * 身份，其他设备退出
 */

namespace Illuminate\Auth\Events;

use Illuminate\Queue\SerializesModels;

class OtherDeviceLogout
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
