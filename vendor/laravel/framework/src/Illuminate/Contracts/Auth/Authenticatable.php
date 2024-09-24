<?php
/**
 * 契约，可认证接口
 */

namespace Illuminate\Contracts\Auth;

interface Authenticatable
{
    /**
     * Get the name of the unique identifier for the user.
	 * 得到用户的唯一标识符的名称
     *
     * @return string
     */
    public function getAuthIdentifierName();

    /**
     * Get the unique identifier for the user.
	 * 得到用户唯一标识符
     *
     * @return mixed
     */
    public function getAuthIdentifier();

    /**
     * Get the password for the user.
	 * 得到用户密码
     *
     * @return string
     */
    public function getAuthPassword();

    /**
     * Get the token value for the "remember me" session.
	 * 得到"记住我"会话的令牌值
     *
     * @return string
     */
    public function getRememberToken();

    /**
     * Set the token value for the "remember me" session.
	 * 设置"记住我"会话的令牌值
     *
     * @param  string  $value
     * @return void
     */
    public function setRememberToken($value);

    /**
     * Get the column name for the "remember me" token.
	 * 得到"记住我"令牌的列名
     *
     * @return string
     */
    public function getRememberTokenName();
}
