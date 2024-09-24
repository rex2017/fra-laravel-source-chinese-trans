<?php
/**
 * 控制台，Artisan开始
 */

namespace Illuminate\Console\Events;

class ArtisanStarting
{
    /**
     * The Artisan application instance.
	 * Artisan应用实例
     *
     * @var \Illuminate\Console\Application
     */
    public $artisan;

    /**
     * Create a new event instance.
	 * 创建新的事件实例
     *
     * @param  \Illuminate\Console\Application  $artisan
     * @return void
     */
    public function __construct($artisan)
    {
        $this->artisan = $artisan;
    }
}
