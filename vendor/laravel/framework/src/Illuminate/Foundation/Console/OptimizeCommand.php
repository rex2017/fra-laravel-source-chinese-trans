<?php
/**
 * 基础，优化命令
 */

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;

class OptimizeCommand extends Command
{
    /**
     * The console command name.
	 * 控制台命令名
     *
     * @var string
     */
    protected $name = 'optimize';

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Cache the framework bootstrap files';

    /**
     * Execute the console command.
	 * 执行控制台命令
     *
     * @return void
     */
    public function handle()
    {
        $this->call('config:cache');
        $this->call('route:cache');

        $this->info('Files cached successfully!');
    }
}
