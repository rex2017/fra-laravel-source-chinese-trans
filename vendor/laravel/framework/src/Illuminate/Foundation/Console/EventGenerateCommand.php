<?php
/**
 * 基础，事件生成命令
 */

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;
use Illuminate\Support\Str;

class EventGenerateCommand extends Command
{
    /**
     * The console command name.
	 * 控制台命令名
     *
     * @var string
     */
    protected $name = 'event:generate';

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Generate the missing events and listeners based on registration';

    /**
     * Execute the console command.
	 * 执行控制台命令
     *
     * @return void
     */
    public function handle()
    {
        $providers = $this->laravel->getProviders(EventServiceProvider::class);

        foreach ($providers as $provider) {
            foreach ($provider->listens() as $event => $listeners) {
                $this->makeEventAndListeners($event, $listeners);
            }
        }

        $this->info('Events and listeners generated successfully!');
    }

    /**
     * Make the event and listeners for the given event.
	 * 创建事件和侦听器为给定事件
     *
     * @param  string  $event
     * @param  array  $listeners
     * @return void
     */
    protected function makeEventAndListeners($event, $listeners)
    {
        if (! Str::contains($event, '\\')) {
            return;
        }

        $this->callSilent('make:event', ['name' => $event]);

        $this->makeListeners($event, $listeners);
    }

    /**
     * Make the listeners for the given event.
	 * 创建监听器为给定事件
     *
     * @param  string  $event
     * @param  array  $listeners
     * @return void
     */
    protected function makeListeners($event, $listeners)
    {
        foreach ($listeners as $listener) {
            $listener = preg_replace('/@.+$/', '', $listener);

            $this->callSilent('make:listener', array_filter(
                ['name' => $listener, '--event' => $event]
            ));
        }
    }
}
