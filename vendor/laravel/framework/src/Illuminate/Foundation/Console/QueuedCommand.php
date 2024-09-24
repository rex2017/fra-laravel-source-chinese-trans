<?php
/**
 * 基础，队列命令
 */

namespace Illuminate\Foundation\Console;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Console\Kernel as KernelContract;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class QueuedCommand implements ShouldQueue
{
    use Dispatchable, Queueable;

    /**
     * The data to pass to the Artisan command.
	 * 传递给Artisan命令的数据
     *
     * @var array
     */
    protected $data;

    /**
     * Create a new job instance.
	 * 创建新的作业实例
     *
     * @param  array  $data
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Handle the job.
	 * 处理作业
     *
     * @param  \Illuminate\Contracts\Console\Kernel  $kernel
     * @return void
     */
    public function handle(KernelContract $kernel)
    {
        $kernel->call(...array_values($this->data));
    }
}
