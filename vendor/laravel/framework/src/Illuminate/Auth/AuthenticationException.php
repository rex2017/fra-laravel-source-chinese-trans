<?php
/**
 * 身份验证异常
 */

namespace Illuminate\Auth;

use Exception;

class AuthenticationException extends Exception
{
    /**
     * All of the guards that were checked.
	 * 所有被检查过的守卫
     *
     * @var array
     */
    protected $guards;

    /**
     * The path the user should be redirected to.
	 * 用户应该重定向到的路径
     *
     * @var string
     */
    protected $redirectTo;

    /**
     * Create a new authentication exception.
	 * 创建新的身份验证异常
     *
     * @param  string  $message
     * @param  array  $guards
     * @param  string|null  $redirectTo
     * @return void
     */
    public function __construct($message = 'Unauthenticated.', array $guards = [], $redirectTo = null)
    {
        parent::__construct($message);

        $this->guards = $guards;
        $this->redirectTo = $redirectTo;
    }

    /**
     * Get the guards that were checked.
	 * 把检查过的守卫找来
     *
     * @return array
     */
    public function guards()
    {
        return $this->guards;
    }

    /**
     * Get the path the user should be redirected to.
	 * 得到用户应该重定向到的路径
     *
     * @return string
     */
    public function redirectTo()
    {
        return $this->redirectTo;
    }
}
