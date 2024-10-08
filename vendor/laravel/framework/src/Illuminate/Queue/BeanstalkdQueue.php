<?php
/**
 * Beanstalkd 队列
 */

namespace Illuminate\Queue;

use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Jobs\BeanstalkdJob;
use Pheanstalk\Job as PheanstalkJob;
use Pheanstalk\Pheanstalk;

class BeanstalkdQueue extends Queue implements QueueContract
{
    /**
     * The Pheanstalk instance.
	 * Pheanstalk实例
     *
     * @var \Pheanstalk\Pheanstalk
     */
    protected $pheanstalk;

    /**
     * The name of the default tube.
	 * 默认管理名
     *
     * @var string
     */
    protected $default;

    /**
     * The "time to run" for all pushed jobs.
	 * 所有被推的工作的"时间到了"
     *
     * @var int
     */
    protected $timeToRun;

    /**
     * The maximum number of seconds to block for a job.
	 * 阻塞作业的最大秒数
     *
     * @var int
     */
    protected $blockFor;

    /**
     * Create a new Beanstalkd queue instance.
	 * 创建新的Beanstalkd队列实例
     *
     * @param  \Pheanstalk\Pheanstalk  $pheanstalk
     * @param  string  $default
     * @param  int  $timeToRun
     * @param  int  $blockFor
     * @return void
     */
    public function __construct(Pheanstalk $pheanstalk, $default, $timeToRun, $blockFor = 0)
    {
        $this->default = $default;
        $this->blockFor = $blockFor;
        $this->timeToRun = $timeToRun;
        $this->pheanstalk = $pheanstalk;
    }

    /**
     * Get the size of the queue.
	 * 得到队列大小
     *
     * @param  string|null  $queue
     * @return int
     */
    public function size($queue = null)
    {
        $queue = $this->getQueue($queue);

        return (int) $this->pheanstalk->statsTube($queue)->current_jobs_ready;
    }

    /**
     * Push a new job onto the queue.
	 * 推送新作业到队列中
     *
     * @param  string  $job
     * @param  mixed  $data
     * @param  string|null  $queue
     * @return mixed
     */
    public function push($job, $data = '', $queue = null)
    {
        return $this->pushRaw($this->createPayload($job, $this->getQueue($queue), $data), $queue);
    }

    /**
     * Push a raw payload onto the queue.
	 * 推入原始有效负载至队列
     *
     * @param  string  $payload
     * @param  string|null  $queue
     * @param  array  $options
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        return $this->pheanstalk->useTube($this->getQueue($queue))->put(
            $payload, Pheanstalk::DEFAULT_PRIORITY, Pheanstalk::DEFAULT_DELAY, $this->timeToRun
        );
    }

    /**
     * Push a new job onto the queue after a delay.
	 * 推入延迟后将新作业至队列
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  string  $job
     * @param  mixed  $data
     * @param  string|null  $queue
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        $pheanstalk = $this->pheanstalk->useTube($this->getQueue($queue));

        return $pheanstalk->put(
            $this->createPayload($job, $this->getQueue($queue), $data),
            Pheanstalk::DEFAULT_PRIORITY,
            $this->secondsUntil($delay),
            $this->timeToRun
        );
    }

    /**
     * Pop the next job off of the queue.
	 * 将下一个作业从队列中弹出
     *
     * @param  string|null  $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    public function pop($queue = null)
    {
        $queue = $this->getQueue($queue);

        $job = $this->pheanstalk->watchOnly($queue)->reserveWithTimeout($this->blockFor);

        if ($job instanceof PheanstalkJob) {
            return new BeanstalkdJob(
                $this->container, $this->pheanstalk, $job, $this->connectionName, $queue
            );
        }
    }

    /**
     * Delete a message from the Beanstalk queue.
	 * 删除消息从Beanstalk队列
     *
     * @param  string  $queue
     * @param  string|int  $id
     * @return void
     */
    public function deleteMessage($queue, $id)
    {
        $queue = $this->getQueue($queue);

        $this->pheanstalk->useTube($queue)->delete(new PheanstalkJob($id, ''));
    }

    /**
     * Get the queue or return the default.
	 * 得到队列或返回默认值
     *
     * @param  string|null  $queue
     * @return string
     */
    public function getQueue($queue)
    {
        return $queue ?: $this->default;
    }

    /**
     * Get the underlying Pheanstalk instance.
	 * 得到底层Beanstalk实例
     *
     * @return \Pheanstalk\Pheanstalk
     */
    public function getPheanstalk()
    {
        return $this->pheanstalk;
    }
}
