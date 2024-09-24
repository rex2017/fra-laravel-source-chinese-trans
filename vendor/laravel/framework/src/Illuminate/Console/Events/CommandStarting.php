<?php
/**
 * 控制台，命令开始
 */

namespace Illuminate\Console\Events;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CommandStarting
{
    /**
     * The command name.
	 * 命令名
     *
     * @var string
     */
    public $command;

    /**
     * The console input implementation.
	 * 控制台输入实现
     *
     * @var \Symfony\Component\Console\Input\InputInterface|null
     */
    public $input;

    /**
     * The command output implementation.
	 * 输出命令实现
     *
     * @var \Symfony\Component\Console\Output\OutputInterface|null
     */
    public $output;

    /**
     * Create a new event instance.
	 * 创建新的事件实例
     *
     * @param  string  $command
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    public function __construct($command, InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->command = $command;
    }
}
