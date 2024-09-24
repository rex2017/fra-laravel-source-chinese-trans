<?php
/**
 * 队列，作业名称
 */

namespace Illuminate\Queue\Jobs;

use Illuminate\Support\Str;

class JobName
{
    /**
     * Parse the given job name into a class / method array.
	 * 解析给定的作业名称为类/方法数组
     *
     * @param  string  $job
     * @return array
     */
    public static function parse($job)
    {
        return Str::parseCallback($job, 'fire');
    }

    /**
     * Get the resolved name of the queued job class.
	 * 得到队列作业类的解析名称
     *
     * @param  string  $name
     * @param  array  $payload
     * @return string
     */
    public static function resolve($name, $payload)
    {
        if (! empty($payload['displayName'])) {
            return $payload['displayName'];
        }

        return $name;
    }
}
