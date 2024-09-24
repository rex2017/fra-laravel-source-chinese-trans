<?php
/**
 * 事件呼叫队列监听者
 */

namespace Illuminate\Events;

use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CallQueuedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The listener class name.
	 * 监听类名
     *
     * @var string
     */
    public $class;

    /**
     * The listener method.
	 * 监听方法
     *
     * @var string
     */
    public $method;

    /**
     * The data to be passed to the listener.
	 * 数据要传递给侦听器的
     *
     * @var array
     */
    public $data;

    /**
     * The number of times the job may be attempted.
	 * 可能尝试该作业的次数
     *
     * @var int
     */
    public $tries;

    /**
     * The number of seconds to wait before retrying the job.
	 * 秒数重试作业之前等待的
     *
     * @var int
     */
    public $retryAfter;

    /**
     * The timestamp indicating when the job should timeout.
	 * 指明作业何时应该超时的时间戳
     *
     * @var int
     */
    public $timeoutAt;

    /**
     * The number of seconds the job can run before timing out.
	 * 可以运行的秒数作业在超时之前
     *
     * @var int
     */
    public $timeout;

    /**
     * Create a new job instance.
	 * 创建新的任务实例
     *
     * @param  string  $class
     * @param  string  $method
     * @param  array  $data
     * @return void
     */
    public function __construct($class, $method, $data)
    {
        $this->data = $data;
        $this->class = $class;
        $this->method = $method;
    }

    /**
     * Handle the queued job.
	 * 处理排队任务
     *
     * @param  \Illuminate\Container\Container  $container
     * @return void
     */
    public function handle(Container $container)
    {
        $this->prepareData();

        $handler = $this->setJobInstanceIfNecessary(
            $this->job, $container->make($this->class)
        );

        $handler->{$this->method}(...array_values($this->data));
    }

    /**
     * Set the job instance of the given class if necessary.
	 * 设置给定类的作业实例如果需要。
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  object  $instance
     * @return object
     */
    protected function setJobInstanceIfNecessary(Job $job, $instance)
    {
        if (in_array(InteractsWithQueue::class, class_uses_recursive($instance))) {
            $instance->setJob($job);
        }

        return $instance;
    }

    /**
     * Call the failed method on the job instance.
	 * 调取失败方法在任务实例中
     *
     * The event instance and the exception will be passed.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function failed($e)
    {
        $this->prepareData();

        $handler = Container::getInstance()->make($this->class);

        $parameters = array_merge(array_values($this->data), [$e]);

        if (method_exists($handler, 'failed')) {
            $handler->failed(...$parameters);
        }
    }

    /**
     * Unserialize the data if needed.
	 * 反序列化
     *
     * @return void
     */
    protected function prepareData()
    {
        if (is_string($this->data)) {
            $this->data = unserialize($this->data);
        }
    }

    /**
     * Get the display name for the queued job.
	 * 得到显示名称
     *
     * @return string
     */
    public function displayName()
    {
        return $this->class;
    }

    /**
     * Prepare the instance for cloning.
	 * 准备克隆实例
     *
     * @return void
     */
    public function __clone()
    {
        $this->data = array_map(function ($data) {
            return is_object($data) ? clone $data : $data;
        }, $this->data);
    }
}
