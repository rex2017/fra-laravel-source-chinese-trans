<?php
/**
 * 广播事件
 */

namespace Illuminate\Broadcasting;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\Broadcaster;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use ReflectionClass;
use ReflectionProperty;

class BroadcastEvent implements ShouldQueue
{
    use Queueable;

    /**
     * The event instance.
	 * 事件实例
     *
     * @var mixed
     */
    public $event;

    /**
     * The number of times the job may be attempted.
	 * 尝试次数
     *
     * @var int
     */
    public $tries;

    /**
     * The number of seconds the job can run before timing out.
	 * 超时秒数
     *
     * @var int
     */
    public $timeout;

    /**
     * Create a new job handler instance.
	 * 创建新的实例
     *
     * @param  mixed  $event
     * @return void
     */
    public function __construct($event)
    {
        $this->event = $event;
        $this->tries = property_exists($event, 'tries') ? $event->tries : null;
        $this->timeout = property_exists($event, 'timeout') ? $event->timeout : null;
    }

    /**
     * Handle the queued job.
	 * 处理队列任务
     *
     * @param  \Illuminate\Contracts\Broadcasting\Broadcaster  $broadcaster
     * @return void
     */
    public function handle(Broadcaster $broadcaster)
    {
        $name = method_exists($this->event, 'broadcastAs')
                ? $this->event->broadcastAs() : get_class($this->event);

        $broadcaster->broadcast(
            Arr::wrap($this->event->broadcastOn()), $name,
            $this->getPayloadFromEvent($this->event)
        );
    }

    /**
     * Get the payload for the given event.
	 * 得到事件的负载
     *
     * @param  mixed  $event
     * @return array
     */
    protected function getPayloadFromEvent($event)
    {
        if (method_exists($event, 'broadcastWith')) {
            return array_merge(
                $event->broadcastWith(), ['socket' => data_get($event, 'socket')]
            );
        }

        $payload = [];

        foreach ((new ReflectionClass($event))->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $payload[$property->getName()] = $this->formatProperty($property->getValue($event));
        }

        unset($payload['broadcastQueue']);

        return $payload;
    }

    /**
     * Format the given value for a property.
	 * 格式化给定属性值
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function formatProperty($value)
    {
        if ($value instanceof Arrayable) {
            return $value->toArray();
        }

        return $value;
    }

    /**
     * Get the display name for the queued job.
	 * 得到队列任务的显示名称
     *
     * @return string
     */
    public function displayName()
    {
        return get_class($this->event);
    }

    /**
     * Prepare the instance for cloning.
	 * 克隆实例做准备
     *
     * @return void
     */
    public function __clone()
    {
        $this->event = clone $this->event;
    }
}
