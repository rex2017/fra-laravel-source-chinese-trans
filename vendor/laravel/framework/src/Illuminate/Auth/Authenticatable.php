<?php
/**
 * 可验证身份
 */

namespace Illuminate\Auth;

trait Authenticatable
{
    /**
     * The column name of the "remember me" token.
	 * 记住我令牌列名
     *
     * @var string
     */
    protected $rememberTokenName = 'remember_token';

    /**
     * Get the name of the unique identifier for the user.
	 * 得到用户的唯一标识符的名称
     *
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return $this->getKeyName();
    }

    /**
     * Get the unique identifier for the user.
	 * 得到用户的唯一标识符
     *
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        return $this->{$this->getAuthIdentifierName()};
    }

    /**
     * Get the password for the user.
	 * 得到用户密码
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->password;
    }

    /**
     * Get the token value for the "remember me" session.
	 * 得到记住我令牌值
     *
     * @return string|null
     */
    public function getRememberToken()
    {
        if (! empty($this->getRememberTokenName())) {
            return (string) $this->{$this->getRememberTokenName()};
        }
    }

    /**
     * Set the token value for the "remember me" session.
	 * 设置记住我令牌值
     *
     * @param  string  $value
     * @return void
     */
    public function setRememberToken($value)
    {
        if (! empty($this->getRememberTokenName())) {
            $this->{$this->getRememberTokenName()} = $value;
        }
    }

    /**
     * Get the column name for the "remember me" token.
	 * 得到记得我令牌列名
     *
     * @return string
     */
    public function getRememberTokenName()
    {
        return $this->rememberTokenName;
    }
}
