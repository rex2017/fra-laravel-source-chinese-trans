<?php
/**
 * 视图，引擎
 */

namespace Illuminate\View\Engines;

abstract class Engine
{
    /**
     * The view that was last to be rendered.
	 * 最后要呈现的视图
     *
     * @var string
     */
    protected $lastRendered;

    /**
     * Get the last view that was rendered.
	 * 得到最后一次渲染的视图
     *
     * @return string
     */
    public function getLastRendered()
    {
        return $this->lastRendered;
    }
}
