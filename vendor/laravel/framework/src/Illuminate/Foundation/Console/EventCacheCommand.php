<?php
/**
 * 基础，事件缓存命令
 */

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;

class EventCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
	 * 控制台命令的名称和签名
     *
     * @var string
     */
    protected $signature = 'event:cache';

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string
     */
    protected $description = "Discover and cache the application's events and listeners";

    /**
     * Execute the console command.
	 * 执行控制台命令
     *
     * @return mixed
     */
    public function handle()
    {
        $this->call('event:clear');

        file_put_contents(
            $this->laravel->getCachedEventsPath(),
            '<?php return '.var_export($this->getEvents(), true).';'
        );

        $this->info('Events cached successfully!');
    }

    /**
     * Get all of the events and listeners configured for the application.
	 * 得到所有事件和监听器的配置为应用
     *
     * @return array
     */
    protected function getEvents()
    {
        $events = [];

        foreach ($this->laravel->getProviders(EventServiceProvider::class) as $provider) {
            $providerEvents = array_merge_recursive($provider->shouldDiscoverEvents() ? $provider->discoverEvents() : [], $provider->listens());

            $events[get_class($provider)] = $providerEvents;
        }

        return $events;
    }
}
