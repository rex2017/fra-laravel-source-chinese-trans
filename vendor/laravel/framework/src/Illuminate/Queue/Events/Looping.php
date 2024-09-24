<?php
/**
 * 队列，循环
 */

namespace Illuminate\Queue\Events;

class Looping
{
    /**
     * The connection name.
	 * 连接名
     *
     * @var string
     */
    public $connectionName;

    /**
     * The queue name.
	 * 队列名
     *
     * @var string
     */
    public $queue;

    /**
     * Create a new event instance.
	 * 创建新的事件实例
     *
     * @param  string  $connectionName
     * @param  string  $queue
     * @return void
     */
    public function __construct($connectionName, $queue)
    {
        $this->queue = $queue;
        $this->connectionName = $connectionName;
    }
}
