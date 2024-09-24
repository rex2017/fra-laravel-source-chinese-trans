<?php
/**
 * 队列，再启动命令
 */

namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\InteractsWithTime;

class RestartCommand extends Command
{
    use InteractsWithTime;

    /**
     * The console command name.
	 * 控制台命令名
     *
     * @var string
     */
    protected $name = 'queue:restart';

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Restart queue worker daemons after their current job';

    /**
     * The cache store implementation.
	 * 缓存存储实现
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * Create a new queue restart command.
	 * 创建新的队列重启命令
     *
     * @param  \Illuminate\Contracts\Cache\Repository  $cache
     * @return void
     */
    public function __construct(Cache $cache)
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
        $this->cache->forever('illuminate:queue:restart', $this->currentTime());

        $this->info('Broadcasting queue restart signal.');
    }
}
