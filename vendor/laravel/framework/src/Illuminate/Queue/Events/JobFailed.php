<?php
/**
 * 队列，作业失败
 */

namespace Illuminate\Queue\Events;

class JobFailed
{
    /**
     * The connection name.
	 * 连接名
     *
     * @var string
     */
    public $connectionName;

    /**
     * The job instance.
	 * 作业实例
     *
     * @var \Illuminate\Contracts\Queue\Job
     */
    public $job;

    /**
     * The exception that caused the job to fail.
	 * 导致作业失败的异常
     *
     * @var \Exception
     */
    public $exception;

    /**
     * Create a new event instance.
	 * 创建新的事件实例
     *
     * @param  string  $connectionName
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  \Exception  $exception
     * @return void
     */
    public function __construct($connectionName, $job, $exception)
    {
        $this->job = $job;
        $this->exception = $exception;
        $this->connectionName = $connectionName;
    }
}
