<?php
/**
 * 队列，Amazon SQS作业
 */

namespace Illuminate\Queue\Jobs;

use Aws\Sqs\SqsClient;
use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Job as JobContract;

class SqsJob extends Job implements JobContract
{
    /**
     * The Amazon SQS client instance.
	 * Amazon SQS客户端实例
     *
     * @var \Aws\Sqs\SqsClient
     */
    protected $sqs;

    /**
     * The Amazon SQS job instance.
	 * Amazon SQS作业实例
     *
     * @var array
     */
    protected $job;

    /**
     * Create a new job instance.
	 * 创建新的作业实例
     *
     * @param  \Illuminate\Container\Container  $container
     * @param  \Aws\Sqs\SqsClient  $sqs
     * @param  array  $job
     * @param  string  $connectionName
     * @param  string  $queue
     * @return void
     */
    public function __construct(Container $container, SqsClient $sqs, array $job, $connectionName, $queue)
    {
        $this->sqs = $sqs;
        $this->job = $job;
        $this->queue = $queue;
        $this->container = $container;
        $this->connectionName = $connectionName;
    }

    /**
     * Release the job back into the queue.
	 * 释放作业返回至队列
     *
     * @param  int  $delay
     * @return void
     */
    public function release($delay = 0)
    {
        parent::release($delay);

        $this->sqs->changeMessageVisibility([
            'QueueUrl' => $this->queue,
            'ReceiptHandle' => $this->job['ReceiptHandle'],
            'VisibilityTimeout' => $delay,
        ]);
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

        $this->sqs->deleteMessage([
            'QueueUrl' => $this->queue, 'ReceiptHandle' => $this->job['ReceiptHandle'],
        ]);
    }

    /**
     * Get the number of times the job has been attempted.
	 * 得到该任务被尝试的次数
     *
     * @return int
     */
    public function attempts()
    {
        return (int) $this->job['Attributes']['ApproximateReceiveCount'];
    }

    /**
     * Get the job identifier.
	 * 得到作业标识符
     *
     * @return string
     */
    public function getJobId()
    {
        return $this->job['MessageId'];
    }

    /**
     * Get the raw body string for the job.
	 * 得到作业的原始主体
     *
     * @return string
     */
    public function getRawBody()
    {
        return $this->job['Body'];
    }

    /**
     * Get the underlying SQS client instance.
	 * 得到底层SQS客户端实例
     *
     * @return \Aws\Sqs\SqsClient
     */
    public function getSqs()
    {
        return $this->sqs;
    }

    /**
     * Get the underlying raw SQS job.
	 * 得到底层SQS作业
     *
     * @return array
     */
    public function getSqsJob()
    {
        return $this->job;
    }
}
