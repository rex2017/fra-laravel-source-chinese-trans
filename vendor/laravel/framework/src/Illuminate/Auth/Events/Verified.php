<?php
/**
 * 身份，已验证
 */

namespace Illuminate\Auth\Events;

use Illuminate\Queue\SerializesModels;

class Verified
{
    use SerializesModels;

    /**
     * The verified user.
	 * 已验证的用户
     *
     * @var \Illuminate\Contracts\Auth\MustVerifyEmail
     */
    public $user;

    /**
     * Create a new event instance.
	 * 创建新的事件实例
     *
     * @param  \Illuminate\Contracts\Auth\MustVerifyEmail  $user
     * @return void
     */
    public function __construct($user)
    {
        $this->user = $user;
    }
}
