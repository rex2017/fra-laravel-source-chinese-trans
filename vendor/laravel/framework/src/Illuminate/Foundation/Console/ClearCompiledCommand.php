<?php
/**
 * 基础，清除编译命令
 */

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;

class ClearCompiledCommand extends Command
{
    /**
     * The console command name.
	 * 控制台命令名
     *
     * @var string
     */
    protected $name = 'clear-compiled';

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Remove the compiled class file';

    /**
     * Execute the console command.
	 * 执行控制台命令
     *
     * @return void
     */
    public function handle()
    {
        if (file_exists($servicesPath = $this->laravel->getCachedServicesPath())) {
            @unlink($servicesPath);
        }

        if (file_exists($packagesPath = $this->laravel->getCachedPackagesPath())) {
            @unlink($packagesPath);
        }

        $this->info('Compiled services and packages files removed!');
    }
}
