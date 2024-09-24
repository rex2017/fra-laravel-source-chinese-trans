<?php
/**
 * 队列，数据库作业记录
 */

namespace Illuminate\Queue\Jobs;

use Illuminate\Support\InteractsWithTime;

class DatabaseJobRecord
{
    use InteractsWithTime;

    /**
     * The underlying job record.
	 * 底层作业记录
     *
     * @var \stdClass
     */
    protected $record;

    /**
     * Create a new job record instance.
	 * 创建新的作业记录实例
     *
     * @param  \stdClass  $record
     * @return void
     */
    public function __construct($record)
    {
        $this->record = $record;
    }

    /**
     * Increment the number of times the job has been attempted.
	 * 增加尝试该作业的次数
     *
     * @return int
     */
    public function increment()
    {
        $this->record->attempts++;

        return $this->record->attempts;
    }

    /**
     * Update the "reserved at" timestamp of the job.
	 * 更新作业的"reserved at"时间戳
     *
     * @return int
     */
    public function touch()
    {
        $this->record->reserved_at = $this->currentTime();

        return $this->record->reserved_at;
    }

    /**
     * Dynamically access the underlying job information.
	 * 动态访问底层作业信息
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->record->{$key};
    }
}
