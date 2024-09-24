<?php
/**
 * 基础，上传命令
 */

namespace Illuminate\Foundation\Console;

use Exception;
use Illuminate\Console\Command;

class UpCommand extends Command
{
    /**
     * The console command name.
	 * 控制台命令名
     *
     * @var string
     */
    protected $name = 'up';

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Bring the application out of maintenance mode';

    /**
     * Execute the console command.
	 * 执行控制台命令
     *
     * @return int
     */
    public function handle()
    {
        try {
            if (! file_exists(storage_path('framework/down'))) {
                $this->comment('Application is already up.');

                return true;
            }

            unlink(storage_path('framework/down'));

            $this->info('Application is now live.');
        } catch (Exception $e) {
            $this->error('Failed to disable maintenance mode.');

            $this->error($e->getMessage());

            return 1;
        }
    }
}
