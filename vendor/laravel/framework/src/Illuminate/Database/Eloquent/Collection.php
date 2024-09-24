<?php
/**
 * 数据库，Eloquent集合
 */

namespace Illuminate\Database\Eloquent;

use Illuminate\Contracts\Queue\QueueableCollection;
use Illuminate\Contracts\Queue\QueueableEntity;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Str;
use LogicException;

class Collection extends BaseCollection implements QueueableCollection
{
    /**
     * Find a model in the collection by key.
	 * 查找集合模型
     *
     * @param  mixed  $key
     * @param  mixed  $default
     * @return \Illuminate\Database\Eloquent\Model|static|null
     */
    public function find($key, $default = null)
    {
        if ($key instanceof Model) {
            $key = $key->getKey();
        }

        if ($key instanceof Arrayable) {
            $key = $key->toArray();
        }

        if (is_array($key)) {
            if ($this->isEmpty()) {
                return new static;
            }

            return $this->whereIn($this->first()->getKeyName(), $key);
        }

        return Arr::first($this->items, function ($model) use ($key) {
            return $model->getKey() == $key;
        }, $default);
    }

    /**
     * Load a set of relationships onto the collection.
	 * 将一组关系加载到集合中
     *
     * @param  array|string  $relations
     * @return $this
     */
    public function load($relations)
    {
        if ($this->isNotEmpty()) {
            if (is_string($relations)) {
                $relations = func_get_args();
            }

            $query = $this->first()->newQueryWithoutRelationships()->with($relations);

            $this->items = $query->eagerLoadRelations($this->items);
        }

        return $this;
    }

    /**
     * Load a set of relationship counts onto the collection.
	 * 将一组关系计数加载到集合中
     *
     * @param  array|string  $relations
     * @return $this
     */
    public function loadCount($relations)
    {
        if ($this->isEmpty()) {
            return $this;
        }

        $models = $this->first()->newModelQuery()
            ->whereKey($this->modelKeys())
            ->select($this->first()->getKeyName())
            ->withCount(...func_get_args())
            ->get();

        $attributes = Arr::except(
            array_keys($models->first()->getAttributes()),
            $models->first()->getKeyName()
        );

        $models->each(function ($model) use ($attributes) {
            $this->find($model->getKey())->forceFill(
                Arr::only($model->getAttributes(), $attributes)
            )->syncOriginalAttributes($attributes);
        });

        return $this;
    }

    /**
     * Load a set of relationships onto the collection if they are not already eager loaded.
	 * 如果一组关系尚未被急切加载，则将它们加载到集合上。
     *
     * @param  array|string  $relations
     * @return $this
     */
    public function loadMissing($relations)
    {
        if (is_string($relations)) {
            $relations = func_get_args();
        }

        foreach ($relations as $key => $value) {
            if (is_numeric($key)) {
                $key = $value;
            }

            $segments = explode('.', explode(':', $key)[0]);

            if (Str::contains($key, ':')) {
                $segments[count($segments) - 1] .= ':'.explode(':', $key)[1];
            }

            $path = [];

            foreach ($segments as $segment) {
                $path[] = [$segment => $segment];
            }

            if (is_callable($value)) {
                $path[count($segments) - 1][end($segments)] = $value;
            }

            $this->loadMissingRelation($this, $path);
        }

        return $this;
    }

    /**
     * Load a relationship path if it is not already eager loaded.
	 * 加载关系路径(如果它还没有被急切加载)
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $models
     * @param  array  $path
     * @return void
     */
    protected function loadMissingRelation(self $models, array $path)
    {
        $relation = array_shift($path);

        $name = explode(':', key($relation))[0];

        if (is_string(reset($relation))) {
            $relation = reset($relation);
        }

        $models->filter(function ($model) use ($name) {
            return ! is_null($model) && ! $model->relationLoaded($name);
        })->load($relation);

        if (empty($path)) {
            return;
        }

        $models = $models->pluck($name)->whereNotNull();

        if ($models->first() instanceof BaseCollection) {
            $models = $models->collapse();
        }

        $this->loadMissingRelation(new static($models), $path);
    }

    /**
     * Load a set of relationships onto the mixed relationship collection.
	 * 将一组关系加载到混合关系集合中
     *
     * @param  string  $relation
     * @param  array  $relations
     * @return $this
     */
    public function loadMorph($relation, $relations)
    {
        $this->pluck($relation)
            ->filter()
            ->groupBy(function ($model) {
                return get_class($model);
            })
            ->each(function ($models, $className) use ($relations) {
                static::make($models)->load($relations[$className] ?? []);
            });

        return $this;
    }

    /**
     * Determine if a key exists in the collection.
	 * 确定一个键是否存在于集合中
     *
     * @param  mixed  $key
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return bool
     */
    public function contains($key, $operator = null, $value = null)
    {
        if (func_num_args() > 1 || $this->useAsCallable($key)) {
            return parent::contains(...func_get_args());
        }

        if ($key instanceof Model) {
            return parent::contains(function ($model) use ($key) {
                return $model->is($key);
            });
        }

        return parent::contains(function ($model) use ($key) {
            return $model->getKey() == $key;
        });
    }

    /**
     * Get the array of primary keys.
	 * 得到主键数组
     *
     * @return array
     */
    public function modelKeys()
    {
        return array_map(function ($model) {
            return $model->getKey();
        }, $this->items);
    }

    /**
     * Merge the collection with the given items.
	 * 合并集合与给定的项
     *
     * @param  \ArrayAccess|array  $items
     * @return static
     */
    public function merge($items)
    {
        $dictionary = $this->getDictionary();

        foreach ($items as $item) {
            $dictionary[$item->getKey()] = $item;
        }

        return new static(array_values($dictionary));
    }

    /**
     * Run a map over each of the items.
	 * 运行一张地图在每个项目上
     *
     * @param  callable  $callback
     * @return \Illuminate\Support\Collection|static
     */
    public function map(callable $callback)
    {
        $result = parent::map($callback);

        return $result->contains(function ($item) {
            return ! $item instanceof Model;
        }) ? $result->toBase() : $result;
    }

    /**
     * Run an associative map over each of the items.
	 * 运行一个关联映射在每个项目上
     *
     * The callback should return an associative array with a single key / value pair.
     *
     * @param  callable  $callback
     * @return \Illuminate\Support\Collection|static
     */
    public function mapWithKeys(callable $callback)
    {
        $result = parent::mapWithKeys($callback);

        return $result->contains(function ($item) {
            return ! $item instanceof Model;
        }) ? $result->toBase() : $result;
    }

    /**
     * Reload a fresh model instance from the database for all the entities.
	 * 为所有实体从数据库中重新加载一个新的模型实例
     *
     * @param  array|string  $with
     * @return static
     */
    public function fresh($with = [])
    {
        if ($this->isEmpty()) {
            return new static;
        }

        $model = $this->first();

        $freshModels = $model->newQueryWithoutScopes()
            ->with(is_string($with) ? func_get_args() : $with)
            ->whereIn($model->getKeyName(), $this->modelKeys())
            ->get()
            ->getDictionary();

        return $this->map(function ($model) use ($freshModels) {
            return $model->exists && isset($freshModels[$model->getKey()])
                    ? $freshModels[$model->getKey()] : null;
        });
    }

    /**
     * Diff the collection with the given items.
	 * 将集合与给定的项进行比较
     *
     * @param  \ArrayAccess|array  $items
     * @return static
     */
    public function diff($items)
    {
        $diff = new static;

        $dictionary = $this->getDictionary($items);

        foreach ($this->items as $item) {
            if (! isset($dictionary[$item->getKey()])) {
                $diff->add($item);
            }
        }

        return $diff;
    }

    /**
     * Intersect the collection with the given items.
	 * 将集合与给定的项目相交
     *
     * @param  \ArrayAccess|array  $items
     * @return static
     */
    public function intersect($items)
    {
        $intersect = new static;

        if (empty($items)) {
            return $intersect;
        }

        $dictionary = $this->getDictionary($items);

        foreach ($this->items as $item) {
            if (isset($dictionary[$item->getKey()])) {
                $intersect->add($item);
            }
        }

        return $intersect;
    }

    /**
     * Return only unique items from the collection.
	 * 只返回集合中唯一的项
     *
     * @param  string|callable|null  $key
     * @param  bool  $strict
     * @return static
     */
    public function unique($key = null, $strict = false)
    {
        if (! is_null($key)) {
            return parent::unique($key, $strict);
        }

        return new static(array_values($this->getDictionary()));
    }

    /**
     * Returns only the models from the collection with the specified keys.
	 * 仅返回集合中具有指定键的模型
     *
     * @param  mixed  $keys
     * @return static
     */
    public function only($keys)
    {
        if (is_null($keys)) {
            return new static($this->items);
        }

        $dictionary = Arr::only($this->getDictionary(), $keys);

        return new static(array_values($dictionary));
    }

    /**
     * Returns all models in the collection except the models with specified keys.
	 * 返回集合中除具有指定键的模型外的所有模型
     *
     * @param  mixed  $keys
     * @return static
     */
    public function except($keys)
    {
        $dictionary = Arr::except($this->getDictionary(), $keys);

        return new static(array_values($dictionary));
    }

    /**
     * Make the given, typically visible, attributes hidden across the entire collection.
	 * 将给定的(通常是可见的)属性隐藏在整个集合中
     *
     * @param  array|string  $attributes
     * @return $this
     */
    public function makeHidden($attributes)
    {
        return $this->each->addHidden($attributes);
    }

    /**
     * Make the given, typically hidden, attributes visible across the entire collection.
	 * 使给定的(通常是隐藏的)属性在整个集合中可见
     *
     * @param  array|string  $attributes
     * @return $this
     */
    public function makeVisible($attributes)
    {
        return $this->each->makeVisible($attributes);
    }

    /**
     * Get a dictionary keyed by primary keys.
	 * 得到以主键为键的字典
     *
     * @param  \ArrayAccess|array|null  $items
     * @return array
     */
    public function getDictionary($items = null)
    {
        $items = is_null($items) ? $this->items : $items;

        $dictionary = [];

        foreach ($items as $value) {
            $dictionary[$value->getKey()] = $value;
        }

        return $dictionary;
    }

    /**
     * The following methods are intercepted to always return base collections.
	 * 以下方法以始终返回基集合
     */

    /**
     * Get an array with the values of a given key.
	 * 得到具有给定键值的数组
     *
     * @param  string|array  $value
     * @param  string|null  $key
     * @return \Illuminate\Support\Collection
     */
    public function pluck($value, $key = null)
    {
        return $this->toBase()->pluck($value, $key);
    }

    /**
     * Get the keys of the collection items.
	 * 得到收集项目的钥匙
     *
     * @return \Illuminate\Support\Collection
     */
    public function keys()
    {
        return $this->toBase()->keys();
    }

    /**
     * Zip the collection together with one or more arrays.
	 * 将集合与一个或多个数组压缩在一起
     *
     * @param  mixed  ...$items
     * @return \Illuminate\Support\Collection
     */
    public function zip($items)
    {
        return $this->toBase()->zip(...func_get_args());
    }

    /**
     * Collapse the collection of items into a single array.
	 * 将项目集合折叠成单个数组
     *
     * @return \Illuminate\Support\Collection
     */
    public function collapse()
    {
        return $this->toBase()->collapse();
    }

    /**
     * Get a flattened array of the items in the collection.
	 * 得到集合中项的扁平数组
     *
     * @param  int  $depth
     * @return \Illuminate\Support\Collection
     */
    public function flatten($depth = INF)
    {
        return $this->toBase()->flatten($depth);
    }

    /**
     * Flip the items in the collection.
	 * 翻转集合中的项目
     *
     * @return \Illuminate\Support\Collection
     */
    public function flip()
    {
        return $this->toBase()->flip();
    }

    /**
     * Pad collection to the specified length with a value.
	 * 垫集合至指定的长度使用值
     *
     * @param  int  $size
     * @param  mixed  $value
     * @return \Illuminate\Support\Collection
     */
    public function pad($size, $value)
    {
        return $this->toBase()->pad($size, $value);
    }

    /**
     * Get the comparison function to detect duplicates.
	 * 得到比较函数以检测重复项
     *
     * @param  bool  $strict
     * @return \Closure
     */
    protected function duplicateComparator($strict)
    {
        return function ($a, $b) {
            return $a->is($b);
        };
    }

    /**
     * Get the type of the entities being queued.
	 * 得到正在排队的实体的类型
     *
     * @return string|null
     *
     * @throws \LogicException
     */
    public function getQueueableClass()
    {
        if ($this->isEmpty()) {
            return;
        }

        $class = get_class($this->first());

        $this->each(function ($model) use ($class) {
            if (get_class($model) !== $class) {
                throw new LogicException('Queueing collections with multiple model types is not supported.');
            }
        });

        return $class;
    }

    /**
     * Get the identifiers for all of the entities.
	 * 得到所有实体的标识符
     *
     * @return array
     */
    public function getQueueableIds()
    {
        if ($this->isEmpty()) {
            return [];
        }

        return $this->first() instanceof QueueableEntity
                    ? $this->map->getQueueableId()->all()
                    : $this->modelKeys();
    }

    /**
     * Get the relationships of the entities being queued.
	 * 得到正在排队的实体之间的关系
     *
     * @return array
     */
    public function getQueueableRelations()
    {
        if ($this->isEmpty()) {
            return [];
        }

        $relations = $this->map->getQueueableRelations()->all();

        if (count($relations) === 0 || $relations === [[]]) {
            return [];
        } elseif (count($relations) === 1) {
            return array_values($relations)[0];
        } else {
            return array_intersect(...$relations);
        }
    }

    /**
     * Get the connection of the entities being queued.
	 * 得到正在排队的实体连接
     *
     * @return string|null
     *
     * @throws \LogicException
     */
    public function getQueueableConnection()
    {
        if ($this->isEmpty()) {
            return;
        }

        $connection = $this->first()->getConnectionName();

        $this->each(function ($model) use ($connection) {
            if ($model->getConnectionName() !== $connection) {
                throw new LogicException('Queueing collections with multiple model connections is not supported.');
            }
        });

        return $connection;
    }
}
