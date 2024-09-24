<?php
/**
 * 解析日志配置
 */

namespace Illuminate\Log;

use InvalidArgumentException;
use Monolog\Logger as Monolog;

trait ParsesLogConfiguration
{
    /**
     * The Log levels.
	 * 日志级别
     *
     * @var array
     */
    protected $levels = [
        'debug' => Monolog::DEBUG,			#调试信息
        'info' => Monolog::INFO,			#一些有意思的事，如登录日志
        'notice' => Monolog::NOTICE,		#普通但重要的事件
        'warning' => Monolog::WARNING,		#警告事件，可能会导致问题
        'error' => Monolog::ERROR,			#错误事件，但不影响系统的正常运行
        'critical' => Monolog::CRITICAL,	#严重错误，可能会导致应用程序中止，比如组件不可用
        'alert' => Monolog::ALERT,			#需要立即采取行动的紧急情况，如数据库不可用
        'emergency' => Monolog::EMERGENCY,	#系统不可用
    ];

    /**
     * Get fallback log channel name.
	 * 得到回退日志渠道名称
     *
     * @return string
     */
    abstract protected function getFallbackChannelName();

    /**
     * Parse the string level into a Monolog constant.
	 * 解析字符串级别
     *
     * @param  array  $config
     * @return int
     *
     * @throws \InvalidArgumentException
     */
    protected function level(array $config)
    {
        $level = $config['level'] ?? 'debug';

        if (isset($this->levels[$level])) {
            return $this->levels[$level];
        }

        throw new InvalidArgumentException('Invalid log level.');
    }

    /**
     * Extract the log channel from the given configuration.
	 * 提取日志通道从配置文件中
     *
     * @param  array  $config
     * @return string
     */
    protected function parseChannel(array $config)
    {
        return $config['name'] ?? $this->getFallbackChannelName();
    }
}
