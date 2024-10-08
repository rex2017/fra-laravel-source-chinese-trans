<?php
/**
 * Http，收集资源
 */

namespace Illuminate\Http\Resources;

use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

trait CollectsResources
{
    /**
     * Map the given collection resource into its individual resources.
	 * 将给定的集合资源映射到它的各个资源
     *
     * @param  mixed  $resource
     * @return mixed
     */
    protected function collectResource($resource)
    {
        if ($resource instanceof MissingValue) {
            return $resource;
        }

        if (is_array($resource)) {
            $resource = new Collection($resource);
        }

        $collects = $this->collects();

        $this->collection = $collects && ! $resource->first() instanceof $collects
            ? $resource->mapInto($collects)
            : $resource->toBase();

        return $resource instanceof AbstractPaginator
                    ? $resource->setCollection($this->collection)
                    : $this->collection;
    }

    /**
     * Get the resource that this resource collects.
	 * 得到此资源收集的资源
     *
     * @return string|null
     */
    protected function collects()
    {
        if ($this->collects) {
            return $this->collects;
        }

        if (Str::endsWith(class_basename($this), 'Collection') &&
            class_exists($class = Str::replaceLast('Collection', '', get_class($this)))) {
            return $class;
        }
    }

    /**
     * Get an iterator for the resource collection.
	 * 得到资源集合的迭代器
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return $this->collection->getIterator();
    }
}
