<?php
/**
 * 契约，控制台内核接口
 */

namespace Illuminate\Contracts\Console;

interface Kernel
{
    /**
     * Handle an incoming console command.
	 * 处理输入的控制台命令
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface|null  $output
     * @return int
     */
    public function handle($input, $output = null);

    /**
     * Run an Artisan console command by name.
	 * 按名称运行Artisan控制台命令
     *
     * @param  string  $command
     * @param  array  $parameters
     * @param  \Symfony\Component\Console\Output\OutputInterface|null  $outputBuffer
     * @return int
     */
    public function call($command, array $parameters = [], $outputBuffer = null);

    /**
     * Queue an Artisan console command by name.
	 * 排队Artisan控制台命令按名称
     *
     * @param  string  $command
     * @param  array  $parameters
     * @return \Illuminate\Foundation\Bus\PendingDispatch
     */
    public function queue($command, array $parameters = []);

    /**
     * Get all of the commands registered with the console.
	 * 得到在控制台注册的所有命令
     *
     * @return array
     */
    public function all();

    /**
     * Get the output for the last run command.
	 * 得到最后一个运行命令的输出
     *
     * @return string
     */
    public function output();

    /**
     * Terminate the application.
	 * 终止应用程序
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  int  $status
     * @return void
     */
    public function terminate($input, $status);
}
