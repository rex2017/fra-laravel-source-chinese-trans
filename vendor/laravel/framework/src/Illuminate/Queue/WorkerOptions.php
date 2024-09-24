<?php
/**
 * 执行者选项
 */

namespace Illuminate\Queue;

class WorkerOptions
{
    /**
     * The number of seconds before a released job will be available.
	 * 在释放的作业可用之前的秒数
     *
     * @var int
     */
    public $delay;

    /**
     * The maximum amount of RAM the worker may consume.
	 * 最大RAM量执行者可能消耗的
     *
     * @var int
     */
    public $memory;

    /**
     * The maximum number of seconds a child worker may run.
	 * 最大秒数子线程可以运行的
     *
     * @var int
     */
    public $timeout;

    /**
     * The number of seconds to wait in between polling the queue.
	 * 最大秒数在轮询队列之间等待
     *
     * @var int
     */
    public $sleep;

    /**
     * The maximum amount of times a job may be attempted.
	 * 最大次数可以尝试作业的
     *
     * @var int
     */
    public $maxTries;

    /**
     * Indicates if the worker should run in maintenance mode.
	 * 指明执行者是否应在维护模式下运行
     *
     * @var bool
     */
    public $force;

    /**
     * Indicates if the worker should stop when queue is empty.
	 * 指明执行者是否应该停止当队列为空时
     *
     * @var bool
     */
    public $stopWhenEmpty;

    /**
     * Create a new worker options instance.
	 * 创建新的执行者选项实例
     *
     * @param  int  $delay
     * @param  int  $memory
     * @param  int  $timeout
     * @param  int  $sleep
     * @param  int  $maxTries
     * @param  bool  $force
     * @param  bool  $stopWhenEmpty
     * @return void
     */
    public function __construct($delay = 0, $memory = 128, $timeout = 60, $sleep = 3, $maxTries = 1, $force = false, $stopWhenEmpty = false)
    {
        $this->delay = $delay;
        $this->sleep = $sleep;
        $this->force = $force;
        $this->memory = $memory;
        $this->timeout = $timeout;
        $this->maxTries = $maxTries;
        $this->stopWhenEmpty = $stopWhenEmpty;
    }
}
