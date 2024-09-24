<?php
/**
 * 契约，密码破解工厂接口
 */

namespace Illuminate\Contracts\Auth;

interface PasswordBrokerFactory
{
    /**
     * Get a password broker instance by name.
	 * 得到密码破解实例
     *
     * @param  string|null  $name
     * @return mixed
     */
    public function broker($name = null);
}
