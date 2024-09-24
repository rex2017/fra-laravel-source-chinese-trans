<?php
/**
 * 路由，无效签名异常
 */

namespace Illuminate\Routing\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidSignatureException extends HttpException
{
    /**
     * Create a new exception instance.
	 * 创建新的异常实例
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct(403, 'Invalid signature.');
    }
}
