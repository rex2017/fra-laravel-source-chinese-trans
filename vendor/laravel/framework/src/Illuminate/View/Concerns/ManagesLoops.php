<?php
/**
 * 视图，管理循环
 */

namespace Illuminate\View\Concerns;

use Countable;
use Illuminate\Support\Arr;

trait ManagesLoops
{
    /**
     * The stack of in-progress loops.
	 * 正在进行的循环的堆栈
     *
     * @var array
     */
    protected $loopsStack = [];

    /**
     * Add new loop to the stack.
	 * 添加新的循环至堆栈
     *
     * @param  \Countable|array  $data
     * @return void
     */
    public function addLoop($data)
    {
        $length = is_array($data) || $data instanceof Countable ? count($data) : null;

        $parent = Arr::last($this->loopsStack);

        $this->loopsStack[] = [
            'iteration' => 0,
            'index' => 0,
            'remaining' => $length ?? null,
            'count' => $length,
            'first' => true,
            'last' => isset($length) ? $length == 1 : null,
            'odd' => false,
            'even' => true,
            'depth' => count($this->loopsStack) + 1,
            'parent' => $parent ? (object) $parent : null,
        ];
    }

    /**
     * Increment the top loop's indices.
	 * 增加顶部循环的索引
     *
     * @return void
     */
    public function incrementLoopIndices()
    {
        $loop = $this->loopsStack[$index = count($this->loopsStack) - 1];

        $this->loopsStack[$index] = array_merge($this->loopsStack[$index], [
            'iteration' => $loop['iteration'] + 1,
            'index' => $loop['iteration'],
            'first' => $loop['iteration'] == 0,
            'odd' => ! $loop['odd'],
            'even' => ! $loop['even'],
            'remaining' => isset($loop['count']) ? $loop['remaining'] - 1 : null,
            'last' => isset($loop['count']) ? $loop['iteration'] == $loop['count'] - 1 : null,
        ]);
    }

    /**
     * Pop a loop from the top of the loop stack.
	 * 弹出一个循环从循环堆栈的顶部
     *
     * @return void
     */
    public function popLoop()
    {
        array_pop($this->loopsStack);
    }

    /**
     * Get an instance of the last loop in the stack.
	 * 得到堆栈中最后一个循环的实例
     *
     * @return \stdClass|null
     */
    public function getLastLoop()
    {
        if ($last = Arr::last($this->loopsStack)) {
            return (object) $last;
        }
    }

    /**
     * Get the entire loop stack.
	 * 得到整个循环堆栈
     *
     * @return array
     */
    public function getLoopStack()
    {
        return $this->loopsStack;
    }
}
