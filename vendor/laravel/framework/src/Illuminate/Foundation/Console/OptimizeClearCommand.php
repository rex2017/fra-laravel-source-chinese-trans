<?php
/**
 * 基础，优化清除命令
 */

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;

class OptimizeClearCommand extends Command
{
    /**
     * The console command name.
	 * 控制台命令名
     *
     * @var string
     */
    protected $name = 'optimize:clear';

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Remove the cached bootstrap files';

    /**
     * Execute the console command.
	 * 执行控制台命令
     *
     * @return void
     */
    public function handle()
    {
        $this->call('view:clear');
        $this->call('cache:clear');
        $this->call('route:clear');
        $this->call('config:clear');
        $this->call('clear-compiled');

        $this->info('Caches cleared successfully!');
    }
}
