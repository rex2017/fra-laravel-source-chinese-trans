<?php
/**
 * 广播频道类
 */

namespace Illuminate\Broadcasting;

class Channel
{
    /**
     * The channel's name.
	 * 频道名
     *
     * @var string
     */
    public $name;

    /**
     * Create a new channel instance.
	 * 创建一个新的渠道实例
     *
     * @param  string  $name
     * @return void
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Convert the channel instance to a string.
	 * 转换渠道实例为字符串
     *
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }
}
