<?php
/**
 * 通知，动作
 */

namespace Illuminate\Notifications;

class Action
{
    /**
     * The action text.
	 * 动作文本
     *
     * @var string
     */
    public $text;

    /**
     * The action URL.
	 * 动作URL
     *
     * @var string
     */
    public $url;

    /**
     * Create a new action instance.
	 * 创建新的动作实例
     *
     * @param  string  $text
     * @param  string  $url
     * @return void
     */
    public function __construct($text, $url)
    {
        $this->url = $url;
        $this->text = $text;
    }
}
