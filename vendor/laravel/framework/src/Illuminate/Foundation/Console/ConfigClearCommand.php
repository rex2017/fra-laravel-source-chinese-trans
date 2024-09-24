<?php
/**
 * 基础，配置清除命令
 */

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class ConfigClearCommand extends Command
{
    /**
     * The console command name.
	 * 控制台命令名
     *
     * @var string
     */
    protected $name = 'config:clear';

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Remove the configuration cache file';

    /**
     * The filesystem instance.
	 * 文件系统实例
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new config clear command instance.
	 * 创建新的配置清除命令实例
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
	 * 执行热控台命令
     *
     * @return void
     */
    public function handle()
    {
        $this->files->delete($this->laravel->getCachedConfigPath());

        $this->info('Configuration cache cleared!');
    }
}
