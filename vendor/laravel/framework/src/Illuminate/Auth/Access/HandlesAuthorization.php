<?php
/**
 * 身份，处理授权
 */

namespace Illuminate\Auth\Access;

trait HandlesAuthorization
{
    /**
     * Create a new access response.
	 * 创建新的访问响应
     *
     * @param  string|null  $message
     * @param  mixed  $code
     * @return \Illuminate\Auth\Access\Response
     */
    protected function allow($message = null, $code = null)
    {
        return Response::allow($message, $code);
    }

    /**
     * Throws an unauthorized exception.
	 * 抛出未经授权的异常
     *
     * @param  string|null  $message
     * @param  mixed|null  $code
     * @return \Illuminate\Auth\Access\Response
     */
    protected function deny($message = null, $code = null)
    {
        return Response::deny($message, $code);
    }
}
