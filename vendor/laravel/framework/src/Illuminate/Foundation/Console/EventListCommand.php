<?php
/**
 * 基础，事件列表命令
 */

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;
use Illuminate\Support\Str;

class EventListCommand extends Command
{
    /**
     * The name and signature of the console command.
	 * 控制台命令的名称和签名
     *
     * @var string
     */
    protected $signature = 'event:list {--event= : Filter the events by name}';

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string
     */
    protected $description = "List the application's events and listeners";

    /**
     * Execute the console command.
	 * 执行控制台命令
     *
     * @return mixed
     */
    public function handle()
    {
        $events = $this->getEvents();

        if (empty($events)) {
            return $this->error("Your application doesn't have any events matching the given criteria.");
        }

        $this->table(['Event', 'Listeners'], $events);
    }

    /**
     * Get all of the events and listeners configured for the application.
	 * 得到所有事件和监听器配置为应用
     *
     * @return array
     */
    protected function getEvents()
    {
        $events = [];

        foreach ($this->laravel->getProviders(EventServiceProvider::class) as $provider) {
            $providerEvents = array_merge_recursive($provider->shouldDiscoverEvents() ? $provider->discoverEvents() : [], $provider->listens());

            $events = array_merge_recursive($events, $providerEvents);
        }

        if ($this->filteringByEvent()) {
            $events = $this->filterEvents($events);
        }

        return collect($events)->map(function ($listeners, $event) {
            return ['Event' => $event, 'Listeners' => implode(PHP_EOL, $listeners)];
        })->sortBy('Event')->values()->toArray();
    }

    /**
     * Filter the given events using the provided event name filter.
	 * 筛选器筛选给定的事件使用提供的事件
     *
     * @param  array  $events
     * @return array
     */
    protected function filterEvents(array $events)
    {
        if (! $eventName = $this->option('event')) {
            return $events;
        }

        return collect($events)->filter(function ($listeners, $event) use ($eventName) {
            return Str::contains($event, $eventName);
        })->toArray();
    }

    /**
     * Determine whether the user is filtering by an event name.
	 * 确定用户是否按事件名称进行过滤
     *
     * @return bool
     */
    protected function filteringByEvent()
    {
        return ! empty($this->option('event'));
    }
}
