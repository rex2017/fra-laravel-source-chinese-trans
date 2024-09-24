<?php
/**
 * 日志，消息记录
 */

namespace Illuminate\Log\Events;

class MessageLogged
{
    /**
     * The log "level".
	 * 日志级别
     *
     * @var string
     */
    public $level;

    /**
     * The log message.
	 * 日志消息
     *
     * @var string
     */
    public $message;

    /**
     * The log context.
	 * 日志内容
     *
     * @var array
     */
    public $context;

    /**
     * Create a new event instance.
	 * 创建新的事件实例
     *
     * @param  string  $level
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function __construct($level, $message, array $context = [])
    {
        $this->level = $level;
        $this->message = $message;
        $this->context = $context;
    }
}
