<?php
/**
 * 队列，忘记失败命令
 */

namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;

class ForgetFailedCommand extends Command
{
    /**
     * The console command signature.
	 * 控制台命令签名
     *
     * @var string
     */
    protected $signature = 'queue:forget {id : The ID of the failed job}';

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Delete a failed queue job';

    /**
     * Execute the console command.
	 * 执行控制台命令
     *
     * @return void
     */
    public function handle()
    {
        if ($this->laravel['queue.failer']->forget($this->argument('id'))) {
            $this->info('Failed job deleted successfully!');
        } else {
            $this->error('No failed job matches the given ID.');
        }
    }
}
