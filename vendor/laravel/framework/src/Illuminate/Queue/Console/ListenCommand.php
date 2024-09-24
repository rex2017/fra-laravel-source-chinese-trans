<?php
/**
 * 队列，监听命令
 */

namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;
use Illuminate\Queue\Listener;
use Illuminate\Queue\ListenerOptions;

class ListenCommand extends Command
{
    /**
     * The console command name.
	 * 控制台命令名
     *
     * @var string
     */
    protected $signature = 'queue:listen
                            {connection? : The name of connection}
                            {--delay=0 : The number of seconds to delay failed jobs}
                            {--force : Force the worker to run even in maintenance mode}
                            {--memory=128 : The memory limit in megabytes}
                            {--queue= : The queue to listen on}
                            {--sleep=3 : Number of seconds to sleep when no job is available}
                            {--timeout=60 : The number of seconds a child process can run}
                            {--tries=1 : Number of times to attempt a job before logging it failed}';

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Listen to a given queue';

    /**
     * The queue listener instance.
	 * 队列监听实例
     *
     * @var \Illuminate\Queue\Listener
     */
    protected $listener;

    /**
     * Create a new queue listen command.
	 * 创建新的队列监听命令
     *
     * @param  \Illuminate\Queue\Listener  $listener
     * @return void
     */
    public function __construct(Listener $listener)
    {
        parent::__construct();

        $this->setOutputHandler($this->listener = $listener);
    }

    /**
     * Execute the console command.
	 * 执行控制台命令
     *
     * @return void
     */
    public function handle()
    {
        // We need to get the right queue for the connection which is set in the queue
        // configuration file for the application. We will pull it based on the set
        // connection being run for the queue operation currently being executed.
		// 我们需要为应用程序的队列配置文件中设置的连接获取正确的队列。
		// 我们将根据当前正在执行的队列操作正在运行的集合连接来拉取它。
        $queue = $this->getQueue(
            $connection = $this->input->getArgument('connection')
        );

        $this->listener->listen(
            $connection, $queue, $this->gatherOptions()
        );
    }

    /**
     * Get the name of the queue connection to listen on.
	 * 得到要侦听的队列连接名
     *
     * @param  string  $connection
     * @return string
     */
    protected function getQueue($connection)
    {
        $connection = $connection ?: $this->laravel['config']['queue.default'];

        return $this->input->getOption('queue') ?: $this->laravel['config']->get(
            "queue.connections.{$connection}.queue", 'default'
        );
    }

    /**
     * Get the listener options for the command.
	 * 得到该命令的侦听器选项
     *
     * @return \Illuminate\Queue\ListenerOptions
     */
    protected function gatherOptions()
    {
        return new ListenerOptions(
            $this->option('env'), $this->option('delay'),
            $this->option('memory'), $this->option('timeout'),
            $this->option('sleep'), $this->option('tries'),
            $this->option('force')
        );
    }

    /**
     * Set the options on the queue listener.
	 * 设置队列监听者选项
     *
     * @param  \Illuminate\Queue\Listener  $listener
     * @return void
     */
    protected function setOutputHandler(Listener $listener)
    {
        $listener->setOutputHandler(function ($type, $line) {
            $this->output->write($line);
        });
    }
}
