<?php
/**
 * 契约，视图引擎接口
 */

namespace Illuminate\Contracts\View;

interface Engine
{
    /**
     * Get the evaluated contents of the view.
	 * 得到一个视图内容
     *
     * @param  string  $path
     * @param  array  $data
     * @return string
     */
    public function get($path, array $data = []);
}
