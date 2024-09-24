<?php
/**
 * 身份，封锁出
 */

namespace Illuminate\Auth\Events;

use Illuminate\Http\Request;

class Lockout
{
    /**
     * The throttled request.
	 * 节流请求
     *
     * @var \Illuminate\Http\Request
     */
    public $request;

    /**
     * Create a new event instance.
	 * 创建新的事件实例
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
}
