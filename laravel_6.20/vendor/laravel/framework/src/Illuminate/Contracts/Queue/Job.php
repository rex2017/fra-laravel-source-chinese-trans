<?php
/**
 * 契约，队列任务接口
 */

namespace Illuminate\Contracts\Queue;

interface Job
{
    /**
     * Get the job identifier.
	 * 得到任务ID
     *
     * @return string
     */
    public function getJobId();

    /**
     * Get the decoded body of the job.
	 * 得到任务的解码正文
     *
     * @return array
     */
    public function payload();

    /**
     * Fire the job.
	 * 注销作业
     *
     * @return void
     */
    public function fire();

    /**
     * Release the job back into the queue.
     * Accepts a delay specified in seconds.
	 * 释放任务返回队列
     *
     * @param  int  $delay
     * @return void
     */
    public function release($delay = 0);

    /**
     * Determine if the job was released back into the queue.
	 * 确定队列是否释放回队列
     *
     * @return bool
     */
    public function isReleased();

    /**
     * Delete the job from the queue.
	 * 删除任务从队列中
     *
     * @return void
     */
    public function delete();

    /**
     * Determine if the job has been deleted.
	 * 确定任务是否被删除
     *
     * @return bool
     */
    public function isDeleted();

    /**
     * Determine if the job has been deleted or released.
	 * 确定任务是否被删除或释放
     *
     * @return bool
     */
    public function isDeletedOrReleased();

    /**
     * Get the number of times the job has been attempted.
	 * 得到任务尝试次数
     *
     * @return int
     */
    public function attempts();

    /**
     * Determine if the job has been marked as a failure.
	 * 确定任务是否被标记为删除 
     *
     * @return bool
     */
    public function hasFailed();

    /**
     * Mark the job as "failed".
	 * 标记任务为失败
     *
     * @return void
     */
    public function markAsFailed();

    /**
     * Delete the job, call the "failed" method, and raise the failed job event.
	 * 删除任务
     *
     * @param  \Throwable|null  $e
     * @return void
     */
    public function fail($e = null);

    /**
     * Get the number of times to attempt a job.
	 * 得到最大尝试次数
     *
     * @return int|null
     */
    public function maxTries();

    /**
     * Get the number of seconds the job can run.
	 * 得到任务执行超时时间
     *
     * @return int|null
     */
    public function timeout();

    /**
     * Get the timestamp indicating when the job should timeout.
     *
     * @return int|null
     */
    public function timeoutAt();

    /**
     * Get the name of the queued job class.
	 * 得到队列任务类名称
     *
     * @return string
     */
    public function getName();

    /**
     * Get the resolved name of the queued job class.
     *
     * Resolves the name of "wrapped" jobs such as class-based handlers.
     *
     * @return string
     */
    public function resolveName();

    /**
     * Get the name of the connection the job belongs to.
     *
     * @return string
     */
    public function getConnectionName();

    /**
     * Get the name of the queue the job belongs to.
	 * 得到所属队列名称
     *
     * @return string
     */
    public function getQueue();

    /**
     * Get the raw body string for the job.
     *
     * @return string
     */
    public function getRawBody();
}
