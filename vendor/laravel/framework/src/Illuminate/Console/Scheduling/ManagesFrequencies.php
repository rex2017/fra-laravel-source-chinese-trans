<?php
/**
 * 控制台，管理频率
 */

namespace Illuminate\Console\Scheduling;

use Illuminate\Support\Carbon;

trait ManagesFrequencies
{
    /**
     * The Cron expression representing the event's frequency.
	 * 表示事件频率的Cron表达式
     *
     * @param  string  $expression
     * @return $this
     */
    public function cron($expression)
    {
        $this->expression = $expression;

        return $this;
    }

    /**
     * Schedule the event to run between start and end time.
	 * 计划事件在开始时间和结束时间之间
     *
     * @param  string  $startTime
     * @param  string  $endTime
     * @return $this
     */
    public function between($startTime, $endTime)
    {
        return $this->when($this->inTimeInterval($startTime, $endTime));
    }

    /**
     * Schedule the event to not run between start and end time.
	 * 计划事件安排不在开始时间和结束时间之间
     *
     * @param  string  $startTime
     * @param  string  $endTime
     * @return $this
     */
    public function unlessBetween($startTime, $endTime)
    {
        return $this->skip($this->inTimeInterval($startTime, $endTime));
    }

    /**
     * Schedule the event to run between start and end time.
	 * 计划事件在开始时间和结束时间之间
     *
     * @param  string  $startTime
     * @param  string  $endTime
     * @return \Closure
     */
    private function inTimeInterval($startTime, $endTime)
    {
        [$now, $startTime, $endTime] = [
            Carbon::now($this->timezone),
            Carbon::parse($startTime, $this->timezone),
            Carbon::parse($endTime, $this->timezone),
        ];

        if ($endTime->lessThan($startTime)) {
            if ($startTime->greaterThan($now)) {
                $startTime->subDay(1);
            } else {
                $endTime->addDay(1);
            }
        }

        return function () use ($now, $startTime, $endTime) {
            return $now->between($startTime, $endTime);
        };
    }

    /**
     * Schedule the event to run every minute.
	 * 计划事件为每分钟运行一次
     *
     * @return $this
     */
    public function everyMinute()
    {
        return $this->spliceIntoPosition(1, '*');
    }

    /**
     * Schedule the event to run every five minutes.
	 * 计划事件为每五分钟运行一次
     *
     * @return $this
     */
    public function everyFiveMinutes()
    {
        return $this->spliceIntoPosition(1, '*/5');
    }

    /**
     * Schedule the event to run every ten minutes.
	 * 计划事件为每十分钟运行一次
     *
     * @return $this
     */
    public function everyTenMinutes()
    {
        return $this->spliceIntoPosition(1, '*/10');
    }

    /**
     * Schedule the event to run every fifteen minutes.
	 * 计划事件为每15分钟一次
	 * 
     *
     * @return $this
     */
    public function everyFifteenMinutes()
    {
        return $this->spliceIntoPosition(1, '*/15');
    }

    /**
     * Schedule the event to run every thirty minutes.
	 * 计划事件为每30分钟进行一次
     *
     * @return $this
     */
    public function everyThirtyMinutes()
    {
        return $this->spliceIntoPosition(1, '0,30');
    }

    /**
     * Schedule the event to run hourly.
	 * 计划事件为每小时运行一次
     *
     * @return $this
     */
    public function hourly()
    {
        return $this->spliceIntoPosition(1, 0);
    }

    /**
     * Schedule the event to run hourly at a given offset in the hour.
	 * 计划事件为按小时内给定的偏移量每小时运行一次
     *
     * @param  array|int  $offset
     * @return $this
     */
    public function hourlyAt($offset)
    {
        $offset = is_array($offset) ? implode(',', $offset) : $offset;

        return $this->spliceIntoPosition(1, $offset);
    }

    /**
     * Schedule the event to run daily.
	 * 计划事件为每天运行一次
     *
     * @return $this
     */
    public function daily()
    {
        return $this->spliceIntoPosition(1, 0)
                    ->spliceIntoPosition(2, 0);
    }

    /**
     * Schedule the command at a given time.
	 * 计划命令在给定时间
     *
     * @param  string  $time
     * @return $this
     */
    public function at($time)
    {
        return $this->dailyAt($time);
    }

    /**
     * Schedule the event to run daily at a given time (10:00, 19:30, etc).
	 * 计划事件在每天的指定时间(10:00,19:30等)
     *
     * @param  string  $time
     * @return $this
     */
    public function dailyAt($time)
    {
        $segments = explode(':', $time);

        return $this->spliceIntoPosition(2, (int) $segments[0])
                    ->spliceIntoPosition(1, count($segments) === 2 ? (int) $segments[1] : '0');
    }

    /**
     * Schedule the event to run twice daily.
	 * 计划事件为每天运行两次
     *
     * @param  int  $first
     * @param  int  $second
     * @return $this
     */
    public function twiceDaily($first = 1, $second = 13)
    {
        $hours = $first.','.$second;

        return $this->spliceIntoPosition(1, 0)
                    ->spliceIntoPosition(2, $hours);
    }

    /**
     * Schedule the event to run only on weekdays.
	 * 计划事件只在工作日运行
     *
     * @return $this
     */
    public function weekdays()
    {
        return $this->spliceIntoPosition(5, '1-5');
    }

    /**
     * Schedule the event to run only on weekends.
	 * 计划事件只在周末进行
     *
     * @return $this
     */
    public function weekends()
    {
        return $this->spliceIntoPosition(5, '0,6');
    }

    /**
     * Schedule the event to run only on Mondays.
	 * 计划事件只在星期一进行
     *
     * @return $this
     */
    public function mondays()
    {
        return $this->days(1);
    }

    /**
     * Schedule the event to run only on Tuesdays.
	 * 计划事件只在星期二进行
     *
     * @return $this
     */
    public function tuesdays()
    {
        return $this->days(2);
    }

    /**
     * Schedule the event to run only on Wednesdays.
	 * 计划事件只在星期三进行
     *
     * @return $this
     */
    public function wednesdays()
    {
        return $this->days(3);
    }

    /**
     * Schedule the event to run only on Thursdays.
	 * 计划事件只在星期四进行
     *
     * @return $this
     */
    public function thursdays()
    {
        return $this->days(4);
    }

    /**
     * Schedule the event to run only on Fridays.
	 * 计划事件只在星期五进行
     *
     * @return $this
     */
    public function fridays()
    {
        return $this->days(5);
    }

    /**
     * Schedule the event to run only on Saturdays.
	 * 计划事件只在星期六进行
     *
     * @return $this
     */
    public function saturdays()
    {
        return $this->days(6);
    }

    /**
     * Schedule the event to run only on Sundays.
	 * 计划事件只在星期天进行
     *
     * @return $this
     */
    public function sundays()
    {
        return $this->days(0);
    }

    /**
     * Schedule the event to run weekly.
	 * 计划事件为每周运行一次
     *
     * @return $this
     */
    public function weekly()
    {
        return $this->spliceIntoPosition(1, 0)
                    ->spliceIntoPosition(2, 0)
                    ->spliceIntoPosition(5, 0);
    }

    /**
     * Schedule the event to run weekly on a given day and time.
	 * 计划事件在每周指定的日期和时间进行
     *
     * @param  int  $day
     * @param  string  $time
     * @return $this
     */
    public function weeklyOn($day, $time = '0:0')
    {
        $this->dailyAt($time);

        return $this->spliceIntoPosition(5, $day);
    }

    /**
     * Schedule the event to run monthly.
	 * 计划事件为每月运行一次
     *
     * @return $this
     */
    public function monthly()
    {
        return $this->spliceIntoPosition(1, 0)
                    ->spliceIntoPosition(2, 0)
                    ->spliceIntoPosition(3, 1);
    }

    /**
     * Schedule the event to run monthly on a given day and time.
	 * 计划事件在每月的特定日期和时间进行
     *
     * @param  int  $day
     * @param  string  $time
     * @return $this
     */
    public function monthlyOn($day = 1, $time = '0:0')
    {
        $this->dailyAt($time);

        return $this->spliceIntoPosition(3, $day);
    }

    /**
     * Schedule the event to run twice monthly.
	 * 计划事件为每月运行两次
     *
     * @param  int  $first
     * @param  int  $second
     * @return $this
     */
    public function twiceMonthly($first = 1, $second = 16)
    {
        $days = $first.','.$second;

        return $this->spliceIntoPosition(1, 0)
            ->spliceIntoPosition(2, 0)
            ->spliceIntoPosition(3, $days);
    }

    /**
     * Schedule the event to run quarterly.
	 * 计划事件为每季度运行一次
     *
     * @return $this
     */
    public function quarterly()
    {
        return $this->spliceIntoPosition(1, 0)
                    ->spliceIntoPosition(2, 0)
                    ->spliceIntoPosition(3, 1)
                    ->spliceIntoPosition(4, '1-12/3');
    }

    /**
     * Schedule the event to run yearly.
	 * 计划事件每年运行一次
     *
     * @return $this
     */
    public function yearly()
    {
        return $this->spliceIntoPosition(1, 0)
                    ->spliceIntoPosition(2, 0)
                    ->spliceIntoPosition(3, 1)
                    ->spliceIntoPosition(4, 1);
    }

    /**
     * Set the days of the week the command should run on.
	 * 设置命令应该运行的星期几
     *
     * @param  array|mixed  $days
     * @return $this
     */
    public function days($days)
    {
        $days = is_array($days) ? $days : func_get_args();

        return $this->spliceIntoPosition(5, implode(',', $days));
    }

    /**
     * Set the timezone the date should be evaluated on.
	 * 设置应该计算日期的时区
     *
     * @param  \DateTimeZone|string  $timezone
     * @return $this
     */
    public function timezone($timezone)
    {
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * Splice the given value into the given position of the expression.
	 * 拼接给定值到表达式的给定位置
     *
     * @param  int  $position
     * @param  string  $value
     * @return $this
     */
    protected function spliceIntoPosition($position, $value)
    {
        $segments = explode(' ', $this->expression);

        $segments[$position - 1] = $value;

        return $this->cron(implode(' ', $segments));
    }
}
