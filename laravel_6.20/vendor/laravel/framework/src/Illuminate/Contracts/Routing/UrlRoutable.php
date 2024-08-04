<?php
/**
 * 契约，URL网址接口
 */

namespace Illuminate\Contracts\Routing;

interface UrlRoutable
{
    /**
     * Get the value of the model's route key.
	 * 得到模型KEY
     *
     * @return mixed
     */
    public function getRouteKey();

    /**
     * Get the route key for the model.
	 * 得到路由Key名
     *
     * @return string
     */
    public function getRouteKeyName();

    /**
     * Retrieve the model for a bound value.
	 * 检索模型绑定值
     *
     * @param  mixed  $value
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function resolveRouteBinding($value);
}
