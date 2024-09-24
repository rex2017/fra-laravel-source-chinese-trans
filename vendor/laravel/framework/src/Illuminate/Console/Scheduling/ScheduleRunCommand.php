<?php
/**
 * 控制台，计划运行命令
 */

namespace Illuminate\Console\Scheduling;

use Illuminate\Console\Command;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Date;

class ScheduleRunCommand extends Command
{
    /**
     * The console command name.
	 * 控制台命令名
     *
     * @var string
     */
    protected $name = 'schedule:run';

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Run the scheduled commands';

    /**
     * The schedule instance.
	 * 计划实例
     *
     * @var \Illuminate\Console\Scheduling\Schedule
     */
    protected $schedule;

    /**
     * The 24 hour timestamp this scheduler command started running.
	 * 这个调度器命令开始运行的时间戳是24小时
     *
     * @var \Illuminate\Support\Carbon
     */
    protected $startedAt;

    /**
     * Check if any events ran.
	 * 检查是否运行了任何事件
     *
     * @var bool
     */
    protected $eventsRan = false;

    /**
     * The event dispatcher.
	 * 事件调度程序
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $dispatcher;

    /**
     * Create a new command instance.
	 * 创建新的命令实例
     *
     * @return void
     */
    public function __construct()
    {
        $this->startedAt = Date::now();

        parent::__construct();
    }

    /**
     * Execute the console command.
	 * 执行控制台命令
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @param  \Illuminate\Contracts\Events\Dispatcher  $dispatcher
     * @return void
     */
    public function handle(Schedule $schedule, Dispatcher $dispatcher)
    {
        $this->schedule = $schedule;
        $this->dispatcher = $dispatcher;

        foreach ($this->schedule->dueEvents($this->laravel) as $event) {
            if (! $event->filtersPass($this->laravel)) {
                $this->dispatcher->dispatch(new ScheduledTaskSkipped($event));

                continue;
            }

            if ($event->onOneServer) {
                $this->runSingleServerEvent($event);
            } else {
                $this->runEvent($event);
            }

            $this->eventsRan = true;
        }

        if (! $this->eventsRan) {
            $this->info('No scheduled commands are ready to run.');
        }
    }

    /**
     * Run the given single server event.
	 * 运行给定的单个服务器事件
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @return void
     */
    protected function runSingleServerEvent($event)
    {
        if ($this->schedule->serverShouldRun($event, $this->startedAt)) {
            $this->runEvent($event);
        } else {
            $this->line('<info>Skipping command (has already run on another server):</info> '.$event->getSummaryForDisplay());
        }
    }

    /**
     * Run the given event.
	 * 运行给定事件
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @return void
     */
    protected function runEvent($event)
    {
        $this->line('<info>Running scheduled command:</info> '.$event->getSummaryForDisplay());

        $this->dispatcher->dispatch(new ScheduledTaskStarting($event));

        $start = microtime(true);

        $event->run($this->laravel);

        $this->dispatcher->dispatch(new ScheduledTaskFinished(
            $event,
            round(microtime(true) - $start, 2)
        ));

        $this->eventsRan = true;
    }
}
