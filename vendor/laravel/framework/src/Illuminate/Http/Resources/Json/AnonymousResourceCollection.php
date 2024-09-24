<?php
/**
 * Http，匿名资源收集
 */

namespace Illuminate\Http\Resources\Json;

class AnonymousResourceCollection extends ResourceCollection
{
    /**
     * The name of the resource being collected.
	 * 正在被收集的资源名称
     *
     * @var string
     */
    public $collects;

    /**
     * Create a new anonymous resource collection.
	 * 创建新的匿名资源集合
     *
     * @param  mixed  $resource
     * @param  string  $collects
     * @return void
     */
    public function __construct($resource, $collects)
    {
        $this->collects = $collects;

        parent::__construct($resource);
    }
}
