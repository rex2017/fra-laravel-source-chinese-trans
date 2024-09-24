<?php
/**
 * Redis，事件命令执行
 */

namespace Illuminate\Redis\Events;

class CommandExecuted
{
    /**
     * The Redis command that was executed.
	 * 被执行的Redis命令
     *
     * @var string
     */
    public $command;

    /**
     * The array of command parameters.
	 * 命令参数数组
     *
     * @var array
     */
    public $parameters;

    /**
     * The number of milliseconds it took to execute the command.
	 * 执行命令所需的毫秒数
     *
     * @var float
     */
    public $time;

    /**
     * The Redis connection instance.
	 * Redis连接实例
     *
     * @var \Illuminate\Redis\Connections\Connection
     */
    public $connection;

    /**
     * The Redis connection name.
	 * Redis连接名称
     *
     * @var string
     */
    public $connectionName;

    /**
     * Create a new event instance.
	 * 创建新的事件实例
     *
     * @param  string  $command
     * @param  array  $parameters
     * @param  float|null  $time
     * @param  \Illuminate\Redis\Connections\Connection  $connection
     * @return void
     */
    public function __construct($command, $parameters, $time, $connection)
    {
        $this->time = $time;
        $this->command = $command;
        $this->parameters = $parameters;
        $this->connection = $connection;
        $this->connectionName = $connection->getName();
    }
}
