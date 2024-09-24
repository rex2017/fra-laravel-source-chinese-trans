<?php
/**
 * 基础，本地更新
 */

namespace Illuminate\Foundation\Events;

class LocaleUpdated
{
    /**
     * The new locale.
	 * 新的本地
     *
     * @var string
     */
    public $locale;

    /**
     * Create a new event instance.
	 * 创建新的事件实例
     *
     * @param  string  $locale
     * @return void
     */
    public function __construct($locale)
    {
        $this->locale = $locale;
    }
}
