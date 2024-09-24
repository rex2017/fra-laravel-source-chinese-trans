<?php
/**
 * 契约，支持基本验证接口
 */

namespace Illuminate\Contracts\Auth;

interface SupportsBasicAuth
{
    /**
     * Attempt to authenticate using HTTP Basic Auth.
	 * 尝试使用HTTP基本认证进行身份验证
     *
     * @param  string  $field
     * @param  array  $extraConditions
     * @return \Symfony\Component\HttpFoundation\Response|null
     */
    public function basic($field = 'email', $extraConditions = []);

    /**
     * Perform a stateless HTTP Basic login attempt.
	 * 进行无状态HTTP基本登录尝试
     *
     * @param  string  $field
     * @param  array  $extraConditions
     * @return \Symfony\Component\HttpFoundation\Response|null
     */
    public function onceBasic($field = 'email', $extraConditions = []);
}
