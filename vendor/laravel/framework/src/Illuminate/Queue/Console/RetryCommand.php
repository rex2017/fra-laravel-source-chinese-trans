<?php
/**
 * 队列，重试命令
 */

namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class RetryCommand extends Command
{
    /**
     * The console command signature.
	 * 控制台命令签名
     *
     * @var string
     */
    protected $signature = 'queue:retry {id* : The ID of the failed job or "all" to retry all jobs}';

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Retry a failed queue job';

    /**
     * Execute the console command.
	 * 执行控制台命令
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->getJobIds() as $id) {
            $job = $this->laravel['queue.failer']->find($id);

            if (is_null($job)) {
                $this->error("Unable to find failed job with ID [{$id}].");
            } else {
                $this->retryJob($job);

                $this->info("The failed job [{$id}] has been pushed back onto the queue!");

                $this->laravel['queue.failer']->forget($id);
            }
        }
    }

    /**
     * Get the job IDs to be retried.
	 * 得到作业IDs
     *
     * @return array
     */
    protected function getJobIds()
    {
        $ids = (array) $this->argument('id');

        if (count($ids) === 1 && $ids[0] === 'all') {
            $ids = Arr::pluck($this->laravel['queue.failer']->all(), 'id');
        }

        return $ids;
    }

    /**
     * Retry the queue job.
     *
     * @param  \stdClass  $job
     * @return void
     */
    protected function retryJob($job)
    {
        $this->laravel['queue']->connection($job->connection)->pushRaw(
            $this->resetAttempts($job->payload), $job->queue
        );
    }

    /**
     * Reset the payload attempts.
	 * 重置负载尝试
     *
     * Applicable to Redis jobs which store attempts in their payload.
     *
     * @param  string  $payload
     * @return string
     */
    protected function resetAttempts($payload)
    {
        $payload = json_decode($payload, true);

        if (isset($payload['attempts'])) {
            $payload['attempts'] = 0;
        }

        return json_encode($payload);
    }
}
