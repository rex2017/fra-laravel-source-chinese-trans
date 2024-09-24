<?php
/**
 * 支持，门面密码
 */

namespace Illuminate\Support\Facades;

use Illuminate\Contracts\Auth\PasswordBroker;

/**
 * @method static string sendResetLink(array $credentials)
 * @method static mixed reset(array $credentials, \Closure $callback)
 *
 * @see \Illuminate\Auth\Passwords\PasswordBroker
 */
class Password extends Facade
{
    /**
     * Constant representing a successfully sent reminder.
	 * 表示成功发送提醒的常量
     *
     * @var string
     */
    const RESET_LINK_SENT = PasswordBroker::RESET_LINK_SENT;

    /**
     * Constant representing a successfully reset password.
	 * 表示成功重置密码的常量
     *
     * @var string
     */
    const PASSWORD_RESET = PasswordBroker::PASSWORD_RESET;

    /**
     * Constant representing the user not found response.
	 * 表示用户未找到响应的常量
     *
     * @var string
     */
    const INVALID_USER = PasswordBroker::INVALID_USER;

    /**
     * Constant representing an invalid token.
	 * 表示无效令牌的常量
     *
     * @var string
     */
    const INVALID_TOKEN = PasswordBroker::INVALID_TOKEN;

    /**
     * Constant representing a throttled reset attempt.
	 * 表示节流复位尝试的常量
     *
     * @var string
     */
    const RESET_THROTTLED = PasswordBroker::RESET_THROTTLED;

    /**
     * Get the registered name of the component.
	 * 得到组件的注册名称
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'auth.password';
    }
}
