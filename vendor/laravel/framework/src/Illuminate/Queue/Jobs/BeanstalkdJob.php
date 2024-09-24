<?php
/**
 * 队列，Beanstalkd任务
 */

namespace Illuminate\Queue\Jobs;

use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Job as JobContract;
use Pheanstalk\Job as PheanstalkJob;
use Pheanstalk\Pheanstalk;

class BeanstalkdJob extends Job implements JobContract
{
    /**
     * The Pheanstalk instance.
	 * Pheanstalk实例
     *
     * @var \Pheanstalk\Pheanstalk
     */
    protected $pheanstalk;

    /**
     * The Pheanstalk job instance.
	 * Pheanstalk任务实例
     *
     * @var \Pheanstalk\Job
     */
    protected $job;

    /**
     * Create a new job instance.
	 * 创建新的任务实例
     *
     * @param  \Illuminate\Container\Container  $container
     * @param  \Pheanstalk\Pheanstalk  $pheanstalk
     * @param  \Pheanstalk\Job  $job
     * @param  string  $connectionName
     * @param  string  $queue
     * @return void
     */
    public function __construct(Container $container, Pheanstalk $pheanstalk, PheanstalkJob $job, $connectionName, $queue)
    {
        $this->job = $job;
        $this->queue = $queue;
        $this->container = $container;
        $this->pheanstalk = $pheanstalk;
        $this->connectionName = $connectionName;
    }

    /**
     * Release the job back into the queue.
	 * 释放作业回队列
     *
     * @param  int  $delay
     * @return void
     */
    public function release($delay = 0)
    {
        parent::release($delay);

        $priority = Pheanstalk::DEFAULT_PRIORITY;

        $this->pheanstalk->release($this->job, $priority, $delay);
    }

    /**
     * Bury the job in the queue.
	 * 插入作业埋至队列中
     *
     * @return void
     */
    public function bury()
    {
        parent::release();

        $this->pheanstalk->bury($this->job);
    }

    /**
     * Delete the job from the queue.
	 * 删除作业从队列
     *
     * @return void
     */
    public function delete()
    {
        parent::delete();

        $this->pheanstalk->delete($this->job);
    }

    /**
     * Get the number of times the job has been attempted.
	 * 得到该作业被尝试的次数
     *
     * @return int
     */
    public function attempts()
    {
        $stats = $this->pheanstalk->statsJob($this->job);

        return (int) $stats->reserves;
    }

    /**
     * Get the job identifier.
	 * 得到作业标识符
     *
     * @return int
     */
    public function getJobId()
    {
        return $this->job->getId();
    }

    /**
     * Get the raw body string for the job.
	 * 得到作业的原始主体字符串
     *
     * @return string
     */
    public function getRawBody()
    {
        return $this->job->getData();
    }

    /**
     * Get the underlying Pheanstalk instance.
	 * 得到底层Pheanstalk实例
     *
     * @return \Pheanstalk\Pheanstalk
     */
    public function getPheanstalk()
    {
        return $this->pheanstalk;
    }

    /**
     * Get the underlying Pheanstalk job.
	 * 得到底层Pheanstalk作业
     *
     * @return \Pheanstalk\Job
     */
    public function getPheanstalkJob()
    {
        return $this->job;
    }
}
