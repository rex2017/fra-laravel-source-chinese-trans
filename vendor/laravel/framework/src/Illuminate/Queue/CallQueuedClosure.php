<?php
/**
 * 调取队列闭包
 */

namespace Illuminate\Queue;

use Closure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use ReflectionFunction;

class CallQueuedClosure implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The serializable Closure instance.
	 * 可序列化的闭包实例
     *
     * @var \Illuminate\Queue\SerializableClosure
     */
    public $closure;

    /**
     * Indicate if the job should be deleted when models are missing.
	 * 指明当模型丢失时是否应该删除作业
     *
     * @var bool
     */
    public $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
	 * 创建新的作业实例
     *
     * @param  \Illuminate\Queue\SerializableClosure  $closure
     * @return void
     */
    public function __construct(SerializableClosure $closure)
    {
        $this->closure = $closure;
    }

    /**
     * Create a new job instance.
	 * 创建新的作业实例
     *
     * @param  \Closure  $job
     * @return self
     */
    public static function create(Closure $job)
    {
        return new self(new SerializableClosure($job));
    }

    /**
     * Execute the job.
	 * 执行作业
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return void
     */
    public function handle(Container $container)
    {
        $container->call($this->closure->getClosure());
    }

    /**
     * Get the display name for the queued job.
	 * 得到队列名称的显示名
     *
     * @return string
     */
    public function displayName()
    {
        $reflection = new ReflectionFunction($this->closure->getClosure());

        return 'Closure ('.basename($reflection->getFileName()).':'.$reflection->getStartLine().')';
    }
}
