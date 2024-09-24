<?php
/**
 * 身份，密码重置
 */

namespace Illuminate\Auth\Events;

use Illuminate\Queue\SerializesModels;

class PasswordReset
{
    use SerializesModels;

    /**
     * The user.
	 * 用户
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable
     */
    public $user;

    /**
     * Create a new event instance.
	 * 创建新的事件实例
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return void
     */
    public function __construct($user)
    {
        $this->user = $user;
    }
}
