<?php
/**
 * 缓存，忘记命令
 */

namespace Illuminate\Cache\Console;

use Illuminate\Cache\CacheManager;
use Illuminate\Console\Command;

class ForgetCommand extends Command
{
    /**
     * The console command name.
	 * 控制台命令名
     *
     * @var string
     */
    protected $signature = 'cache:forget {key : The key to remove} {store? : The store to remove the key from}';

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Remove an item from the cache';

    /**
     * The cache manager instance.
	 * 缓存管理实例
     *
     * @var \Illuminate\Cache\CacheManager
     */
    protected $cache;

    /**
     * Create a new cache clear command instance.
	 * 创建新的缓存清除命令实例
     *
     * @param  \Illuminate\Cache\CacheManager  $cache
     * @return void
     */
    public function __construct(CacheManager $cache)
    {
        parent::__construct();

        $this->cache = $cache;
    }

    /**
     * Execute the console command.
	 * 执行控制台命令
     *
     * @return void
     */
    public function handle()
    {
        $this->cache->store($this->argument('store'))->forget(
            $this->argument('key')
        );

        $this->info('The ['.$this->argument('key').'] key has been removed from the cache.');
    }
}
