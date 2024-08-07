<?php
/**
 * 契约，可渲染接口
 */

namespace Illuminate\Contracts\Support;

interface Renderable
{
    /**
     * Get the evaluated contents of the object.
	 * 获取对接的评估内容
     *
     * @return string
     */
    public function render();
}
