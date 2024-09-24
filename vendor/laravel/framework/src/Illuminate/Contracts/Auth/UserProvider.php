<?php
/**
 * 契约，用户提供者接口
 */

namespace Illuminate\Contracts\Auth;

interface UserProvider
{
    /**
     * Retrieve a user by their unique identifier.
	 * 检索用户根据用户的唯一标识符
     *
     * @param  mixed  $identifier
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById($identifier);

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
	 * 根据用户的唯一标识符和"记住我"令牌检索用户
     *
     * @param  mixed  $identifier
     * @param  string  $token
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByToken($identifier, $token);

    /**
     * Update the "remember me" token for the given user in storage.
	 * 更新存储中给定用户的"记住我"令牌
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $token
     * @return void
     */
    public function updateRememberToken(Authenticatable $user, $token);

    /**
     * Retrieve a user by the given credentials.
	 * 根据给定的凭据检索用户
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials);

    /**
     * Validate a user against the given credentials.
	 * 验证用户根据给定的凭据
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $credentials
     * @return bool
     */
    public function validateCredentials(Authenticatable $user, array $credentials);
}
