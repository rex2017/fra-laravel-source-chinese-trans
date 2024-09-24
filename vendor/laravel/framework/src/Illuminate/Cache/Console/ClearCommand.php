<?php
/**
 * 缓存，清除命令
 */

namespace Illuminate\Cache\Console;

use Illuminate\Cache\CacheManager;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ClearCommand extends Command
{
    /**
     * The console command name.
	 * 控制台命令名
     *
     * @var string
     */
    protected $name = 'cache:clear';

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Flush the application cache';

    /**
     * The cache manager instance.
	 * 缓存管理实例
     *
     * @var \Illuminate\Cache\CacheManager
     */
    protected $cache;

    /**
     * The filesystem instance.
	 * 文件系统实例
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new cache clear command instance.
	 * 创建新的缓存清除命令实例
     *
     * @param  \Illuminate\Cache\CacheManager  $cache
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(CacheManager $cache, Filesystem $files)
    {
        parent::__construct();

        $this->cache = $cache;
        $this->files = $files;
    }

    /**
     * Execute the console command.
	 * 执行控制台命令
     *
     * @return void
     */
    public function handle()
    {
        $this->laravel['events']->dispatch(
            'cache:clearing', [$this->argument('store'), $this->tags()]
        );

        $successful = $this->cache()->flush();

        $this->flushFacades();

        if (! $successful) {
            return $this->error('Failed to clear cache. Make sure you have the appropriate permissions.');
        }

        $this->laravel['events']->dispatch(
            'cache:cleared', [$this->argument('store'), $this->tags()]
        );

        $this->info('Application cache cleared!');
    }

    /**
     * Flush the real-time facades stored in the cache directory.
	 * 刷新存储在缓存目录中的实时facade
     *
     * @return void
     */
    public function flushFacades()
    {
        if (! $this->files->exists($storagePath = storage_path('framework/cache'))) {
            return;
        }

        foreach ($this->files->files($storagePath) as $file) {
            if (preg_match('/facade-.*\.php$/', $file)) {
                $this->files->delete($file);
            }
        }
    }

    /**
     * Get the cache instance for the command.
	 * 得到命令的缓存实例
     *
     * @return \Illuminate\Cache\Repository
     */
    protected function cache()
    {
        $cache = $this->cache->store($this->argument('store'));

        return empty($this->tags()) ? $cache : $cache->tags($this->tags());
    }

    /**
     * Get the tags passed to the command.
	 * 得到传递给命令的标记
     *
     * @return array
     */
    protected function tags()
    {
        return array_filter(explode(',', $this->option('tags')));
    }

    /**
     * Get the console command arguments.
	 * 得到控制台命令参数
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['store', InputArgument::OPTIONAL, 'The name of the store you would like to clear'],
        ];
    }

    /**
     * Get the console command options.
	 * 得到控制台命令选项
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['tags', null, InputOption::VALUE_OPTIONAL, 'The cache tags you would like to clear', null],
        ];
    }
}
