<?php
/**
 * 契约，控制台应用接口
 */

namespace Illuminate\Contracts\Console;

interface Application
{
    /**
     * Run an Artisan console command by name.
	 * 执行一个客户端命令
     *
     * @param  string  $command
     * @param  array  $parameters
     * @param  \Symfony\Component\Console\Output\OutputInterface|null  $outputBuffer
     * @return int
     */
    public function call($command, array $parameters = [], $outputBuffer = null);

    /**
     * Get the output from the last command.
	 * 得到上一个命令的输出
     *
     * @return string
     */
    public function output();
}
