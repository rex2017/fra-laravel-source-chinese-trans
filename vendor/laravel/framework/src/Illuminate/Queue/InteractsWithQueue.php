<?php
/**
 * 队列，调取队列闭包
 */

namespace Illuminate\Queue;

use Illuminate\Contracts\Queue\Job as JobContract;

trait InteractsWithQueue
{
    /**
     * The underlying queue job instance.
	 * 底层队列作业实例
     *
     * @var \Illuminate\Contracts\Queue\Job
     */
    protected $job;

    /**
     * Get the number of times the job has been attempted.
	 * 得到该任务被尝试的次数
     *
     * @return int
     */
    public function attempts()
    {
        return $this->job ? $this->job->attempts() : 1;
    }

    /**
     * Delete the job from the queue.
	 * 删除作业从队列
     *
     * @return void
     */
    public function delete()
    {
        if ($this->job) {
            return $this->job->delete();
        }
    }

    /**
     * Fail the job from the queue.
	 * 失败作业从队列中
     *
     * @param  \Throwable|null  $exception
     * @return void
     */
    public function fail($exception = null)
    {
        if ($this->job) {
            $this->job->fail($exception);
        }
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
        if ($this->job) {
            return $this->job->release($delay);
        }
    }

    /**
     * Set the base queue job instance.
	 * 设置基本队列实例
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @return $this
     */
    public function setJob(JobContract $job)
    {
        $this->job = $job;

        return $this;
    }
}
