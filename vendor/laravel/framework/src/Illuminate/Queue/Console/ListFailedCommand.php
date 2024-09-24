<?php
/**
 * 队列，失败命令列表
 */

namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class ListFailedCommand extends Command
{
    /**
     * The console command name.
	 * 控制台命令名
     *
     * @var string
     */
    protected $name = 'queue:failed';

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'List all of the failed queue jobs';

    /**
     * The table headers for the command.
	 * 命令表头
     *
     * @var array
     */
    protected $headers = ['ID', 'Connection', 'Queue', 'Class', 'Failed At'];

    /**
     * Execute the console command.
	 * 执行控制台命令
     *
     * @return void
     */
    public function handle()
    {
        if (count($jobs = $this->getFailedJobs()) === 0) {
            return $this->info('No failed jobs!');
        }

        $this->displayFailedJobs($jobs);
    }

    /**
     * Compile the failed jobs into a displayable format.
	 * 编译失败的作业为可显示的格式
     *
     * @return array
     */
    protected function getFailedJobs()
    {
        $failed = $this->laravel['queue.failer']->all();

        return collect($failed)->map(function ($failed) {
            return $this->parseFailedJob((array) $failed);
        })->filter()->all();
    }

    /**
     * Parse the failed job row.
	 * 解析失败作业行
     *
     * @param  array  $failed
     * @return array
     */
    protected function parseFailedJob(array $failed)
    {
        $row = array_values(Arr::except($failed, ['payload', 'exception']));

        array_splice($row, 3, 0, $this->extractJobName($failed['payload']));

        return $row;
    }

    /**
     * Extract the failed job name from payload.
	 * 提取失败的作业名称从有效负载中
     *
     * @param  string  $payload
     * @return string|null
     */
    private function extractJobName($payload)
    {
        $payload = json_decode($payload, true);

        if ($payload && (! isset($payload['data']['command']))) {
            return $payload['job'] ?? null;
        } elseif ($payload && isset($payload['data']['command'])) {
            return $this->matchJobName($payload);
        }
    }

    /**
     * Match the job name from the payload.
	 * 匹配作业名称从有效负载
     *
     * @param  array  $payload
     * @return string
     */
    protected function matchJobName($payload)
    {
        preg_match('/"([^"]+)"/', $payload['data']['command'], $matches);

        return $matches[1] ?? $payload['job'] ?? null;
    }

    /**
     * Display the failed jobs in the console.
	 * 在控制台中显示失败的作业
     *
     * @param  array  $jobs
     * @return void
     */
    protected function displayFailedJobs(array $jobs)
    {
        $this->table($this->headers, $jobs);
    }
}
