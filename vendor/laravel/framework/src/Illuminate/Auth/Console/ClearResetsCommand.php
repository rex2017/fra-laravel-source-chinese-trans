<?php
/**
 * 身份，控制台清除命令
 */

namespace Illuminate\Auth\Console;

use Illuminate\Console\Command;

class ClearResetsCommand extends Command
{
    /**
     * The name and signature of the console command.
	 * 控制台命令的名称和签名
     *
     * @var string
     */
    protected $signature = 'auth:clear-resets {name? : The name of the password broker}';

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Flush expired password reset tokens';

    /**
     * Execute the console command.
	 * 执行控制台命令
     *
     * @return void
     */
    public function handle()
    {
        $this->laravel['auth.password']->broker($this->argument('name'))->getRepository()->deleteExpired();

        $this->info('Expired reset tokens cleared!');
    }
}
