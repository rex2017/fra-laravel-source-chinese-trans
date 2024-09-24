<?php
/**
 * 契约，视图接口
 */

namespace Illuminate\Contracts\View;

use Illuminate\Contracts\Support\Renderable;

interface View extends Renderable
{
    /**
     * Get the name of the view.
	 * 得到视图名称
     *
     * @return string
     */
    public function name();

    /**
     * Add a piece of data to the view.
	 * 添加一条数据至视图
     *
     * @param  string|array  $key
     * @param  mixed  $value
     * @return $this
     */
    public function with($key, $value = null);

    /**
     * Get the array of view data.
	 * 得到视图内容
     *
     * @return array
     */
    public function getData();
}
