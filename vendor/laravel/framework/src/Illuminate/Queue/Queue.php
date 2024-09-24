<?php
/**
 * 队列抽象类
 */

namespace Illuminate\Queue;

use DateTimeInterface;
use Illuminate\Container\Container;
use Illuminate\Support\InteractsWithTime;

abstract class Queue
{
    use InteractsWithTime;

    /**
     * The IoC container instance.
	 * 容器实例
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * The connection name for the queue.
	 * 队列连接名
     *
     * @var string
     */
    protected $connectionName;

    /**
     * The create payload callbacks.
	 * 创建有效负载回调
     *
     * @var callable[]
     */
    protected static $createPayloadCallbacks = [];

    /**
     * Push a new job onto the queue.
	 * 推动新作业至队列中
     *
     * @param  string  $queue
     * @param  string  $job
     * @param  mixed  $data
     * @return mixed
     */
    public function pushOn($queue, $job, $data = '')
    {
        return $this->push($job, $data, $queue);
    }

    /**
     * Push a new job onto the queue after a delay.
	 * 推入新作业至队列在延迟后
     *
     * @param  string  $queue
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  string  $job
     * @param  mixed  $data
     * @return mixed
     */
    public function laterOn($queue, $delay, $job, $data = '')
    {
        return $this->later($delay, $job, $data, $queue);
    }

    /**
     * Push an array of jobs onto the queue.
	 * 推入一组作业至队列
     *
     * @param  array  $jobs
     * @param  mixed  $data
     * @param  string|null  $queue
     * @return void
     */
    public function bulk($jobs, $data = '', $queue = null)
    {
        foreach ((array) $jobs as $job) {
            $this->push($job, $data, $queue);
        }
    }

    /**
     * Create a payload string from the given job and data.
	 * 创建有效负载字符串根据给定的作业和数据
     *
     * @param  string|object  $job
     * @param  string  $queue
     * @param  mixed  $data
     * @return string
     *
     * @throws \Illuminate\Queue\InvalidPayloadException
     */
    protected function createPayload($job, $queue, $data = '')
    {
        $payload = json_encode($this->createPayloadArray($job, $queue, $data));

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidPayloadException(
                'Unable to JSON encode payload. Error code: '.json_last_error()
            );
        }

        return $payload;
    }

    /**
     * Create a payload array from the given job and data.
	 * 创建有效负载数组根据给定的作业和数据
     *
     * @param  string|object  $job
     * @param  string  $queue
     * @param  mixed  $data
     * @return array
     */
    protected function createPayloadArray($job, $queue, $data = '')
    {
        return is_object($job)
                    ? $this->createObjectPayload($job, $queue)
                    : $this->createStringPayload($job, $queue, $data);
    }

    /**
     * Create a payload for an object-based queue handler.
	 * 创建有效负载为基于对象的队列处理程序
     *
     * @param  object  $job
     * @param  string  $queue
     * @return array
     */
    protected function createObjectPayload($job, $queue)
    {
        $payload = $this->withCreatePayloadHooks($queue, [
            'displayName' => $this->getDisplayName($job),
            'job' => 'Illuminate\Queue\CallQueuedHandler@call',
            'maxTries' => $job->tries ?? null,
            'delay' => $this->getJobRetryDelay($job),
            'timeout' => $job->timeout ?? null,
            'timeoutAt' => $this->getJobExpiration($job),
            'data' => [
                'commandName' => $job,
                'command' => $job,
            ],
        ]);

        return array_merge($payload, [
            'data' => [
                'commandName' => get_class($job),
                'command' => serialize(clone $job),
            ],
        ]);
    }

    /**
     * Get the display name for the given job.
	 * 得到给定作业的显示名称
     *
     * @param  object  $job
     * @return string
     */
    protected function getDisplayName($job)
    {
        return method_exists($job, 'displayName')
                        ? $job->displayName() : get_class($job);
    }

    /**
     * Get the retry delay for an object-based queue handler.
	 * 得到基于对象的队列处理程序的重试延迟
     *
     * @param  mixed  $job
     * @return mixed
     */
    public function getJobRetryDelay($job)
    {
        if (! method_exists($job, 'retryAfter') && ! isset($job->retryAfter)) {
            return;
        }

        $delay = $job->retryAfter ?? $job->retryAfter();

        return $delay instanceof DateTimeInterface
                        ? $this->secondsUntil($delay) : $delay;
    }

    /**
     * Get the expiration timestamp for an object-based queue handler.
	 * 得到基于对象的队列处理程序的过期时间戳
     *
     * @param  mixed  $job
     * @return mixed
     */
    public function getJobExpiration($job)
    {
        if (! method_exists($job, 'retryUntil') && ! isset($job->timeoutAt)) {
            return;
        }

        $expiration = $job->timeoutAt ?? $job->retryUntil();

        return $expiration instanceof DateTimeInterface
                        ? $expiration->getTimestamp() : $expiration;
    }

    /**
     * Create a typical, string based queue payload array.
	 * 创建一个典型的、基于字符串的队列有效负载数组
     *
     * @param  string  $job
     * @param  string  $queue
     * @param  mixed  $data
     * @return array
     */
    protected function createStringPayload($job, $queue, $data)
    {
        return $this->withCreatePayloadHooks($queue, [
            'displayName' => is_string($job) ? explode('@', $job)[0] : null,
            'job' => $job,
            'maxTries' => null,
            'delay' => null,
            'timeout' => null,
            'data' => $data,
        ]);
    }

    /**
     * Register a callback to be executed when creating job payloads.
	 * 注册一个回调，以便在创建作业有效负载时执行。
     *
     * @param  callable  $callback
     * @return void
     */
    public static function createPayloadUsing($callback)
    {
        if (is_null($callback)) {
            static::$createPayloadCallbacks = [];
        } else {
            static::$createPayloadCallbacks[] = $callback;
        }
    }

    /**
     * Create the given payload using any registered payload hooks.
	 * 创建给定的有效负载使用任何已注册的有效负载钩子
     *
     * @param  string  $queue
     * @param  array  $payload
     * @return array
     */
    protected function withCreatePayloadHooks($queue, array $payload)
    {
        if (! empty(static::$createPayloadCallbacks)) {
            foreach (static::$createPayloadCallbacks as $callback) {
                $payload = array_merge($payload, call_user_func(
                    $callback, $this->getConnectionName(), $queue, $payload
                ));
            }
        }

        return $payload;
    }

    /**
     * Get the connection name for the queue.
	 * 得到队列的连接名称
     *
     * @return string
     */
    public function getConnectionName()
    {
        return $this->connectionName;
    }

    /**
     * Set the connection name for the queue.
	 * 设置队列的连接名称
     *
     * @param  string  $name
     * @return $this
     */
    public function setConnectionName($name)
    {
        $this->connectionName = $name;

        return $this;
    }

    /**
     * Set the IoC container instance.
	 * 设置IoC容器实例
     *
     * @param  \Illuminate\Container\Container  $container
     * @return void
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
    }
}
