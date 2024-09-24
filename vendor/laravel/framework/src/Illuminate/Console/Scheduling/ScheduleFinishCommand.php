<?php
/**
 * 控制台，计划完成命令
 */

namespace Illuminate\Console\Scheduling;

use Illuminate\Console\Command;

class ScheduleFinishCommand extends Command
{
    /**
     * The console command name.
	 * 控制台命令名
     *
     * @var string
     */
    protected $signature = 'schedule:finish {id} {code=0}';

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Handle the completion of a scheduled command';

    /**
     * Indicates whether the command should be shown in the Artisan command list.
	 * 指明该命令是否应该显示在Artisan命令列表中
     *
     * @var bool
     */
    protected $hidden = true;

    /**
     * Execute the console command.
	 * 执行控制台命令
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    public function handle(Schedule $schedule)
    {
        collect($schedule->events())->filter(function ($value) {
            return $value->mutexName() == $this->argument('id');
        })->each->callAfterCallbacksWithExitCode($this->laravel, $this->argument('code'));
    }
}
