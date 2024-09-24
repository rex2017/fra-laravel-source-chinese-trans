<?php
/**
 * 路由排序中间件
 */

namespace Illuminate\Routing;

use Illuminate\Support\Collection;

class SortedMiddleware extends Collection
{
    /**
     * Create a new Sorted Middleware container.
	 * 创建新的排序中间件容器
     *
     * @param  array  $priorityMap
     * @param  \Illuminate\Support\Collection|array  $middlewares
     * @return void
     */
    public function __construct(array $priorityMap, $middlewares)
    {
        if ($middlewares instanceof Collection) {
            $middlewares = $middlewares->all();
        }

        $this->items = $this->sortMiddleware($priorityMap, $middlewares);
    }

    /**
     * Sort the middlewares by the given priority map.
	 * 排序中间件根据给定的优先级映射
     *
     * Each call to this method makes one discrete middleware movement if necessary.
     *
     * @param  array  $priorityMap
     * @param  array  $middlewares
     * @return array
     */
    protected function sortMiddleware($priorityMap, $middlewares)
    {
        $lastIndex = 0;

        foreach ($middlewares as $index => $middleware) {
            if (! is_string($middleware)) {
                continue;
            }

            $stripped = head(explode(':', $middleware));

            if (in_array($stripped, $priorityMap)) {
                $priorityIndex = array_search($stripped, $priorityMap);

                // This middleware is in the priority map. If we have encountered another middleware
                // that was also in the priority map and was at a lower priority than the current
                // middleware, we will move this middleware to be above the previous encounter.
				// 此中间件位于优先级图中。如果我们遇到了另一个也在优先级映射中并且优先级低于当前中间件的中间件，
				// 我们将把这个中间件移动到之前遇到的中间件之上。
                if (isset($lastPriorityIndex) && $priorityIndex < $lastPriorityIndex) {
                    return $this->sortMiddleware(
                        $priorityMap, array_values($this->moveMiddleware($middlewares, $index, $lastIndex))
                    );
                }

                // This middleware is in the priority map; but, this is the first middleware we have
                // encountered from the map thus far. We'll save its current index plus its index
                // from the priority map so we can compare against them on the next iterations.
				// 该中间件位于优先级图中；但是，这是我们迄今为止在map中遇到的第一个中间件。
				// 我们将保存其当前索引以及优先级图中的索引，以便在下一次迭代中与它们进行比较。
                $lastIndex = $index;
                $lastPriorityIndex = $priorityIndex;
            }
        }

        return Router::uniqueMiddleware($middlewares);
    }

    /**
     * Splice a middleware into a new position and remove the old entry.
	 * 拼接中间件到新位置并删除旧条目
     *
     * @param  array  $middlewares
     * @param  int  $from
     * @param  int  $to
     * @return array
     */
    protected function moveMiddleware($middlewares, $from, $to)
    {
        array_splice($middlewares, $to, 0, $middlewares[$from]);

        unset($middlewares[$from + 1]);

        return $middlewares;
    }
}
