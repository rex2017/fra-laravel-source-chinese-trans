<?php
/**
 * 容器可回放生成器
 */

namespace Illuminate\Container;

use Countable;
use IteratorAggregate;

class RewindableGenerator implements Countable, IteratorAggregate
{
    /**
     * The generator callback.
	 * 生成回调
     *
     * @var callable
     */
    protected $generator;

    /**
     * The number of tagged services.
	 * 目标服务数
     *
     * @var callable|int
     */
    protected $count;

    /**
     * Create a new generator instance.
	 * 创建新的生成器实例
     *
     * @param  callable  $generator
     * @param  callable|int  $count
     * @return void
     */
    public function __construct(callable $generator, $count)
    {
        $this->count = $count;
        $this->generator = $generator;
    }

    /**
     * Get an iterator from the generator.
	 * 得到迭代器从生成器
     *
     * @return mixed
     */
    public function getIterator()
    {
        return ($this->generator)();
    }

    /**
     * Get the total number of tagged services.
	 * 得到标记服务的总数
     *
     * @return int
     */
    public function count()
    {
        if (is_callable($count = $this->count)) {
            $this->count = $count();
        }

        return $this->count;
    }
}
