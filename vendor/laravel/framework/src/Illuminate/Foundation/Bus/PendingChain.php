<?php
/**
 * 基础总线，等待链
 */

namespace Illuminate\Foundation\Bus;

class PendingChain
{
    /**
     * The class name of the job being dispatched.
	 * 正被分派的任务类
     *
     * @var string
     */
    public $class;

    /**
     * The jobs to be chained.
	 * 任务链
     *
     * @var array
     */
    public $chain;

    /**
     * Create a new PendingChain instance.
	 * 创建新的实例
     *
     * @param  string  $class
     * @param  array  $chain
     * @return void
     */
    public function __construct($class, $chain)
    {
        $this->class = $class;
        $this->chain = $chain;
    }

    /**
     * Dispatch the job with the given arguments.
     *
     * @return \Illuminate\Foundation\Bus\PendingDispatch
     */
    public function dispatch()
    {
        return (new PendingDispatch(
            new $this->class(...func_get_args())
        ))->chain($this->chain);
    }
}
