<?php
/**
 * 契约，路由绑定注册接口
 */

namespace Illuminate\Contracts\Routing;

interface BindingRegistrar
{
    /**
     * Add a new route parameter binder.
	 * 添加新的路由参加绑定
     *
     * @param  string  $key
     * @param  string|callable  $binder
     * @return void
     */
    public function bind($key, $binder);

    /**
     * Get the binding callback for a given binding.
	 * 得到给定绑定的绑定回调
     *
     * @param  string  $key
     * @return \Closure
     */
    public function getBindingCallback($key);
}
