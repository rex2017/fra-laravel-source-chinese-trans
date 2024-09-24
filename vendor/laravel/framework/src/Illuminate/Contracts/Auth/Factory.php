<?php
/**
 * 契约，认证工厂接口
 */

namespace Illuminate\Contracts\Auth;

interface Factory
{
    /**
     * Get a guard instance by name.
	 * 得到守卫实例
     *
     * @param  string|null  $name
     * @return \Illuminate\Contracts\Auth\Guard|\Illuminate\Contracts\Auth\StatefulGuard
     */
    public function guard($name = null);

    /**
     * Set the default guard the factory should serve.
	 * 设置工厂应该提供的默认保护
     *
     * @param  string  $name
     * @return void
     */
    public function shouldUse($name);
}
