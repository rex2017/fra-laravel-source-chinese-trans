<?php
/**
 * 队列执行者
 */

namespace Illuminate\Queue;

use Exception;
use Illuminate\Contracts\Cache\Repository as CacheContract;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\Factory as QueueManager;
use Illuminate\Database\DetectsLostConnections;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\Looping;
use Illuminate\Queue\Events\WorkerStopping;
use Illuminate\Support\Carbon;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Throwable;

class Worker
{
    use DetectsLostConnections;

    /**
     * The queue manager instance.
	 * 队列管理实例
     *
     * @var \Illuminate\Contracts\Queue\Factory
     */
    protected $manager;

    /**
     * The event dispatcher instance.
	 * 事件调度实例
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * The cache repository implementation.
	 * 缓存资源库实现
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * The exception handler instance.
	 * 异常处理实例
     *
     * @var \Illuminate\Contracts\Debug\ExceptionHandler
     */
    protected $exceptions;

    /**
     * The callback used to determine if the application is in maintenance mode.
	 * 回调用于确定应用程序是否处于维护模式
     *
     * @var callable
     */
    protected $isDownForMaintenance;

    /**
     * Indicates if the worker should exit.
	 * 指明执行者是否应该退出
     *
     * @var bool
     */
    public $shouldQuit = false;

    /**
     * Indicates if the worker is paused.
	 * 指明执行者是否已暂停
     *
     * @var bool
     */
    public $paused = false;

    /**
     * Create a new queue worker.、
	 * 创建新的队列执行者
     *
     * @param  \Illuminate\Contracts\Queue\Factory  $manager
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @param  \Illuminate\Contracts\Debug\ExceptionHandler  $exceptions
     * @param  callable  $isDownForMaintenance
     * @return void
     */
    public function __construct(QueueManager $manager,
                                Dispatcher $events,
                                ExceptionHandler $exceptions,
                                callable $isDownForMaintenance)
    {
        $this->events = $events;
        $this->manager = $manager;
        $this->exceptions = $exceptions;
        $this->isDownForMaintenance = $isDownForMaintenance;
    }

    /**
     * Listen to the given queue in a loop.
	 * 监听给定队列在循环中
     *
     * @param  string  $connectionName
     * @param  string  $queue
     * @param  \Illuminate\Queue\WorkerOptions  $options
     * @return void
     */
    public function daemon($connectionName, $queue, WorkerOptions $options)
    {
        if ($this->supportsAsyncSignals()) {
            $this->listenForSignals();
        }

        $lastRestart = $this->getTimestampOfLastQueueRestart();

        while (true) {
            // Before reserving any jobs, we will make sure this queue is not paused and
            // if it is we will just pause this worker for a given amount of time and
            // make sure we do not need to kill this worker process off completely.
			// 在保留任何作业之前，我们将确保此队列未暂停，如果暂停，我们将暂停此工作进程一段时间，
			// 并确保不需要完全关闭此工作进程。
            if (! $this->daemonShouldRun($options, $connectionName, $queue)) {
                $this->pauseWorker($options, $lastRestart);

                continue;
            }

            // First, we will attempt to get the next job off of the queue. We will also
            // register the timeout handler and reset the alarm for this job so it is
            // not stuck in a frozen state forever. Then, we can fire off this job.
			// 首先，我们将尝试从队列中删除下一个作业。
			// 我们还将注册超时处理程序并重置此作业的警报，这样它就不会永远处于冻结状态。
			// 然后，我们可以解雇这份工作。
            $job = $this->getNextJob(
                $this->manager->connection($connectionName), $queue
            );

            if ($this->supportsAsyncSignals()) {
                $this->registerTimeoutHandler($job, $options);
            }

            // If the daemon should run (not in maintenance mode, etc.), then we can run
            // fire off this job for processing. Otherwise, we will need to sleep the
            // worker so no more jobs are processed until they should be processed.
			// 如果守护进程应该运行（而不是在维护模式下等），那么我们可以运行fire-off此作业进行处理。
			// 否则，我们需要让执行者睡觉，这样在应该处理之前，就不会再处理任何工作。
            if ($job) {
                $this->runJob($job, $connectionName, $options);
            } else {
                $this->sleep($options->sleep);
            }

            if ($this->supportsAsyncSignals()) {
                $this->resetTimeoutHandler();
            }

            // Finally, we will check to see if we have exceeded our memory limits or if
            // the queue should restart based on other indications. If so, we'll stop
            // this worker and let whatever is "monitoring" it restart the process.
			// 最后，我们将检查是否已超出内存限制，或者队列是否应根据其他指示重新启动。
			// 如果是这样，我们将停止此worker，让任何“监视”它的东西重新启动进程。
            $this->stopIfNecessary($options, $lastRestart, $job);
        }
    }

    /**
     * Register the worker timeout handler.
	 * 注册执行者超时处理程序
     *
     * @param  \Illuminate\Contracts\Queue\Job|null  $job
     * @param  \Illuminate\Queue\WorkerOptions  $options
     * @return void
     */
    protected function registerTimeoutHandler($job, WorkerOptions $options)
    {
        // We will register a signal handler for the alarm signal so that we can kill this
        // process if it is running too long because it has frozen. This uses the async
        // signals supported in recent versions of PHP to accomplish it conveniently.
		// 我们将为报警信号注册一个信号处理程序，以便在进程因冻结而运行时间过长时可以终止此进程。
		// 这使用了最新版本的PHP中支持的异步信号来方便地完成它。
        pcntl_signal(SIGALRM, function () use ($job, $options) {
            if ($job) {
                $this->markJobAsFailedIfWillExceedMaxAttempts(
                    $job->getConnectionName(), $job, (int) $options->maxTries, $this->maxAttemptsExceededException($job)
                );
            }

            $this->kill(1);
        });

        pcntl_alarm(
            max($this->timeoutForJob($job, $options), 0)
        );
    }

    /**
     * Reset the worker timeout handler.
	 * 重置工作超时处理程序
     *
     * @return void
     */
    protected function resetTimeoutHandler()
    {
        pcntl_alarm(0);
    }

    /**
     * Get the appropriate timeout for the given job.
	 * 得到给定作业的适当超时
     *
     * @param  \Illuminate\Contracts\Queue\Job|null  $job
     * @param  \Illuminate\Queue\WorkerOptions  $options
     * @return int
     */
    protected function timeoutForJob($job, WorkerOptions $options)
    {
        return $job && ! is_null($job->timeout()) ? $job->timeout() : $options->timeout;
    }

    /**
     * Determine if the daemon should process on this iteration.
	 * 确定守护进程是否应该在此迭代中进行处理
     *
     * @param  \Illuminate\Queue\WorkerOptions  $options
     * @param  string  $connectionName
     * @param  string  $queue
     * @return bool
     */
    protected function daemonShouldRun(WorkerOptions $options, $connectionName, $queue)
    {
        return ! ((($this->isDownForMaintenance)() && ! $options->force) ||
            $this->paused ||
            $this->events->until(new Looping($connectionName, $queue)) === false);
    }

    /**
     * Pause the worker for the current loop.
	 * 暂停执行者为当前循环
     *
     * @param  \Illuminate\Queue\WorkerOptions  $options
     * @param  int  $lastRestart
     * @return void
     */
    protected function pauseWorker(WorkerOptions $options, $lastRestart)
    {
        $this->sleep($options->sleep > 0 ? $options->sleep : 1);

        $this->stopIfNecessary($options, $lastRestart);
    }

    /**
     * Stop the process if necessary.
	 * 请停止该进程如有必要
     *
     * @param  \Illuminate\Queue\WorkerOptions  $options
     * @param  int  $lastRestart
     * @param  mixed  $job
     * @return void
     */
    protected function stopIfNecessary(WorkerOptions $options, $lastRestart, $job = null)
    {
        if ($this->shouldQuit) {
            $this->stop();
        } elseif ($this->memoryExceeded($options->memory)) {
            $this->stop(12);
        } elseif ($this->queueShouldRestart($lastRestart)) {
            $this->stop();
        } elseif ($options->stopWhenEmpty && is_null($job)) {
            $this->stop();
        }
    }

    /**
     * Process the next job on the queue.
	 * 处理队列上的下一个作业
     *
     * @param  string  $connectionName
     * @param  string  $queue
     * @param  \Illuminate\Queue\WorkerOptions  $options
     * @return void
     */
    public function runNextJob($connectionName, $queue, WorkerOptions $options)
    {
        $job = $this->getNextJob(
            $this->manager->connection($connectionName), $queue
        );

        // If we're able to pull a job off of the stack, we will process it and then return
        // from this method. If there is no job on the queue, we will "sleep" the worker
        // for the specified number of seconds, then keep processing jobs after sleep.
		// 如果我们能够从堆栈中提取一个作业，我们将处理它，然后从这个方法返回。
		// 如果队列中没有作业，我们将使worker“休眠”指定的秒数，然后在休眠后继续处理作业。
        if ($job) {
            return $this->runJob($job, $connectionName, $options);
        }

        $this->sleep($options->sleep);
    }

    /**
     * Get the next job from the queue connection.
	 * 得到下一个作业从队列连接中
     *
     * @param  \Illuminate\Contracts\Queue\Queue  $connection
     * @param  string  $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    protected function getNextJob($connection, $queue)
    {
        try {
            foreach (explode(',', $queue) as $queue) {
                if (! is_null($job = $connection->pop($queue))) {
                    return $job;
                }
            }
        } catch (Exception $e) {
            $this->exceptions->report($e);

            $this->stopWorkerIfLostConnection($e);

            $this->sleep(1);
        } catch (Throwable $e) {
            $this->exceptions->report($e = new FatalThrowableError($e));

            $this->stopWorkerIfLostConnection($e);

            $this->sleep(1);
        }
    }

    /**
     * Process the given job.
	 * 处理给定的作业
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  string  $connectionName
     * @param  \Illuminate\Queue\WorkerOptions  $options
     * @return void
     */
    protected function runJob($job, $connectionName, WorkerOptions $options)
    {
        try {
            return $this->process($connectionName, $job, $options);
        } catch (Exception $e) {
            $this->exceptions->report($e);

            $this->stopWorkerIfLostConnection($e);
        } catch (Throwable $e) {
            $this->exceptions->report($e = new FatalThrowableError($e));

            $this->stopWorkerIfLostConnection($e);
        }
    }

    /**
     * Stop the worker if we have lost connection to a database.
	 * 停止执行者如果我们失去了数据库连接
     *
     * @param  \Throwable  $e
     * @return void
     */
    protected function stopWorkerIfLostConnection($e)
    {
        if ($this->causedByLostConnection($e)) {
            $this->shouldQuit = true;
        }
    }

    /**
     * Process the given job from the queue.
	 * 处理给定的作业从队列中
     *
     * @param  string  $connectionName
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  \Illuminate\Queue\WorkerOptions  $options
     * @return void
     *
     * @throws \Throwable
     */
    public function process($connectionName, $job, WorkerOptions $options)
    {
        try {
            // First we will raise the before job event and determine if the job has already ran
            // over its maximum attempt limits, which could primarily happen when this job is
            // continually timing out and not actually throwing any exceptions from itself.
			// 首先，我们将引发作业前事件，并确定作业是否已经超过其最大尝试限制，
			// 这可能主要发生在该作业持续超时且实际上没有抛出任何异常的情况下。
            $this->raiseBeforeJobEvent($connectionName, $job);

            $this->markJobAsFailedIfAlreadyExceedsMaxAttempts(
                $connectionName, $job, (int) $options->maxTries
            );

            if ($job->isDeleted()) {
                return $this->raiseAfterJobEvent($connectionName, $job);
            }

            // Here we will fire off the job and let it process. We will catch any exceptions so
            // they can be reported to the developers logs, etc. Once the job is finished the
            // proper events will be fired to let any listeners know this job has finished.
			// 在这里，我们将解雇这项工作，让它继续进行。我们将捕获任何异常，以便将其报告给开发人员日志等。
			// 一旦作业完成，将触发适当的事件，让任何侦听器知道此作业已完成。
            $job->fire();

            $this->raiseAfterJobEvent($connectionName, $job);
        } catch (Exception $e) {
            $this->handleJobException($connectionName, $job, $options, $e);
        } catch (Throwable $e) {
            $this->handleJobException(
                $connectionName, $job, $options, new FatalThrowableError($e)
            );
        }
    }

    /**
     * Handle an exception that occurred while the job was running.
	 * 处理作业运行时发生的异常
     *
     * @param  string  $connectionName
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  \Illuminate\Queue\WorkerOptions  $options
     * @param  \Exception  $e
     * @return void
     *
     * @throws \Exception
     */
    protected function handleJobException($connectionName, $job, WorkerOptions $options, $e)
    {
        try {
            // First, we will go ahead and mark the job as failed if it will exceed the maximum
            // attempts it is allowed to run the next time we process it. If so we will just
            // go ahead and mark it as failed now so we do not have to release this again.
			// 首先，如果作业超过下次处理时允许运行的最大尝试次数，我们将继续将其标记为失败。
			// 如果是这样，我们现在将继续将它标记为失败，这样我们就不必再次发布它。
            if (! $job->hasFailed()) {
                $this->markJobAsFailedIfWillExceedMaxAttempts(
                    $connectionName, $job, (int) $options->maxTries, $e
                );
            }

            $this->raiseExceptionOccurredJobEvent(
                $connectionName, $job, $e
            );
        } finally {
            // If we catch an exception, we will attempt to release the job back onto the queue
            // so it is not lost entirely. This'll let the job be retried at a later time by
            // another listener (or this same one). We will re-throw this exception after.
			// 如果我们捕获到异常，我们将尝试将作业释放回队列，这样它就不会完全丢失。
			// 这将允许另一个监听器（或同一个监听器）稍后重试该作业。之后我们将重新抛出此例外。
            if (! $job->isDeleted() && ! $job->isReleased() && ! $job->hasFailed()) {
                $job->release(
                    method_exists($job, 'delaySeconds') && ! is_null($job->delaySeconds())
                                ? $job->delaySeconds()
                                : $options->delay
                );
            }
        }

        throw $e;
    }

    /**
     * Mark the given job as failed if it has exceeded the maximum allowed attempts.
	 * 标记给定作业为失败如果它超过了允许的最大尝试次数
     *
     * This will likely be because the job previously exceeded a timeout.
     *
     * @param  string  $connectionName
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  int  $maxTries
     * @return void
     */
    protected function markJobAsFailedIfAlreadyExceedsMaxAttempts($connectionName, $job, $maxTries)
    {
        $maxTries = ! is_null($job->maxTries()) ? $job->maxTries() : $maxTries;

        $timeoutAt = $job->timeoutAt();

        if ($timeoutAt && Carbon::now()->getTimestamp() <= $timeoutAt) {
            return;
        }

        if (! $timeoutAt && ($maxTries === 0 || $job->attempts() <= $maxTries)) {
            return;
        }

        $this->failJob($job, $e = $this->maxAttemptsExceededException($job));

        throw $e;
    }

    /**
     * Mark the given job as failed if it has exceeded the maximum allowed attempts.
	 * 标记给定作业为失败如果它超过了允许的最大尝试次数
     *
     * @param  string  $connectionName
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  int  $maxTries
     * @param  \Exception  $e
     * @return void
     */
    protected function markJobAsFailedIfWillExceedMaxAttempts($connectionName, $job, $maxTries, $e)
    {
        $maxTries = ! is_null($job->maxTries()) ? $job->maxTries() : $maxTries;

        if ($job->timeoutAt() && $job->timeoutAt() <= Carbon::now()->getTimestamp()) {
            $this->failJob($job, $e);
        }

        if ($maxTries > 0 && $job->attempts() >= $maxTries) {
            $this->failJob($job, $e);
        }
    }

    /**
     * Mark the given job as failed and raise the relevant event.
	 * 标记给定的作业为失败并引发相关事件
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  \Exception  $e
     * @return void
     */
    protected function failJob($job, $e)
    {
        return $job->fail($e);
    }

    /**
     * Raise the before queue job event.
	 * 引发before队列作业事件
     *
     * @param  string  $connectionName
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @return void
     */
    protected function raiseBeforeJobEvent($connectionName, $job)
    {
        $this->events->dispatch(new JobProcessing(
            $connectionName, $job
        ));
    }

    /**
     * Raise the after queue job event.
	 * 引发队列后作业事件
     *
     * @param  string  $connectionName
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @return void
     */
    protected function raiseAfterJobEvent($connectionName, $job)
    {
        $this->events->dispatch(new JobProcessed(
            $connectionName, $job
        ));
    }

    /**
     * Raise the exception occurred queue job event.
	 * 引发异常发生的队列作业事件
     *
     * @param  string  $connectionName
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  \Exception  $e
     * @return void
     */
    protected function raiseExceptionOccurredJobEvent($connectionName, $job, $e)
    {
        $this->events->dispatch(new JobExceptionOccurred(
            $connectionName, $job, $e
        ));
    }

    /**
     * Determine if the queue worker should restart.
	 * 确定队列执行者是否应该重新启动
     *
     * @param  int|null  $lastRestart
     * @return bool
     */
    protected function queueShouldRestart($lastRestart)
    {
        return $this->getTimestampOfLastQueueRestart() != $lastRestart;
    }

    /**
     * Get the last queue restart timestamp, or null.
	 * 得到最后一次队列重启时间戳，或空。
     *
     * @return int|null
     */
    protected function getTimestampOfLastQueueRestart()
    {
        if ($this->cache) {
            return $this->cache->get('illuminate:queue:restart');
        }
    }

    /**
     * Enable async signals for the process.
	 * 启用异步信号为进程
     *
     * @return void
     */
    protected function listenForSignals()
    {
        pcntl_async_signals(true);

        pcntl_signal(SIGTERM, function () {
            $this->shouldQuit = true;
        });

        pcntl_signal(SIGUSR2, function () {
            $this->paused = true;
        });

        pcntl_signal(SIGCONT, function () {
            $this->paused = false;
        });
    }

    /**
     * Determine if "async" signals are supported.
	 * 确定是否支持"async"信号
     *
     * @return bool
     */
    protected function supportsAsyncSignals()
    {
        return extension_loaded('pcntl');
    }

    /**
     * Determine if the memory limit has been exceeded.
	 * 确定是否已超过内存限制
     *
     * @param  int  $memoryLimit
     * @return bool
     */
    public function memoryExceeded($memoryLimit)
    {
        return (memory_get_usage(true) / 1024 / 1024) >= $memoryLimit;
    }

    /**
     * Stop listening and bail out of the script.
	 * 别再听了，跳出剧本。
     *
     * @param  int  $status
     * @return void
     */
    public function stop($status = 0)
    {
        $this->events->dispatch(new WorkerStopping($status));

        exit($status);
    }

    /**
     * Kill the process.
	 * 结束进程
     *
     * @param  int  $status
     * @return void
     */
    public function kill($status = 0)
    {
        $this->events->dispatch(new WorkerStopping($status));

        if (extension_loaded('posix')) {
            posix_kill(getmypid(), SIGKILL);
        }

        exit($status);
    }

    /**
     * Create an instance of MaxAttemptsExceededException.
	 * 创建MaxAttemptsExceededException实例
     *
     * @param  \Illuminate\Contracts\Queue\Job|null  $job
     * @return \Illuminate\Queue\MaxAttemptsExceededException
     */
    protected function maxAttemptsExceededException($job)
    {
        return new MaxAttemptsExceededException(
            $job->resolveName().' has been attempted too many times or run too long. The job may have previously timed out.'
        );
    }

    /**
     * Sleep the script for a given number of seconds.
	 * 休眠脚本给定的秒数
     *
     * @param  int|float  $seconds
     * @return void
     */
    public function sleep($seconds)
    {
        if ($seconds < 1) {
            usleep($seconds * 1000000);
        } else {
            sleep($seconds);
        }
    }

    /**
     * Set the cache repository implementation.
	 * 设置缓存存储库实现
     *
     * @param  \Illuminate\Contracts\Cache\Repository  $cache
     * @return void
     */
    public function setCache(CacheContract $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Get the queue manager instance.
	 * 得到队列管理实例
     *
     * @return \Illuminate\Queue\QueueManager
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * Set the queue manager instance.
	 * 设置队列管理实例
     *
     * @param  \Illuminate\Contracts\Queue\Factory  $manager
     * @return void
     */
    public function setManager(QueueManager $manager)
    {
        $this->manager = $manager;
    }
}
