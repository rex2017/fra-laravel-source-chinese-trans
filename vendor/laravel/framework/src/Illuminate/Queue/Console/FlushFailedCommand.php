<?php
/**
 * 队列，刷新失败命令
 */

namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;

class FlushFailedCommand extends Command
{
    /**
     * The console command name.
	 * 控制台命令名
     *
     * @var string
     */
    protected $name = 'queue:flush';

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Flush all of the failed queue jobs';

    /**
     * Execute the console command.
	 * 执行控制台命令
     *
     * @return void
     */
    public function handle()
    {
        $this->laravel['queue.failer']->flush();

        $this->info('All failed jobs deleted successfully!');
    }
}
