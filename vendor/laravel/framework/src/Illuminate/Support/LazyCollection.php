<?php
/**
 * 支持，惰性收集
 */

namespace Illuminate\Support;

use ArrayIterator;
use Closure;
use Illuminate\Support\Traits\EnumeratesValues;
use Illuminate\Support\Traits\Macroable;
use IteratorAggregate;
use stdClass;

class LazyCollection implements Enumerable
{
    use EnumeratesValues, Macroable;

    /**
     * The source from which to generate items.
	 * 要从中生成项的资源
     *
     * @var callable|static
     */
    public $source;

    /**
     * Create a new lazy collection instance.
	 * 创建新的惰性集合实例
     *
     * @param  mixed  $source
     * @return void
     */
    public function __construct($source = null)
    {
        if ($source instanceof Closure || $source instanceof self) {
            $this->source = $source;
        } elseif (is_null($source)) {
            $this->source = static::empty();
        } else {
            $this->source = $this->getArrayableItems($source);
        }
    }

    /**
     * Create a new instance with no items.
	 * 创建一个没有项目的新实例
     *
     * @return static
     */
    public static function empty()
    {
        return new static([]);
    }

    /**
     * Create a new instance by invoking the callback a given amount of times.
	 * 创建一个新实例通过调用给定次数的回调
     *
     * @param  int  $number
     * @param  callable  $callback
     * @return static
     */
    public static function times($number, callable $callback = null)
    {
        if ($number < 1) {
            return new static;
        }

        $instance = new static(function () use ($number) {
            for ($current = 1; $current <= $number; $current++) {
                yield $current;
            }
        });

        return is_null($callback) ? $instance : $instance->map($callback);
    }

    /**
     * Create an enumerable with the given range.
	 * 创建一个可枚举对象用给定的范围
     *
     * @param  int  $from
     * @param  int  $to
     * @return static
     */
    public static function range($from, $to)
    {
        return new static(function () use ($from, $to) {
            for (; $from <= $to; $from++) {
                yield $from;
            }
        });
    }

    /**
     * Get all items in the enumerable.
	 * 得到枚举中的所有项
     *
     * @return array
     */
    public function all()
    {
        if (is_array($this->source)) {
            return $this->source;
        }

        return iterator_to_array($this->getIterator());
    }

    /**
     * Eager load all items into a new lazy collection backed by an array.
	 * 将所有项加载到由数组支持的新惰性集合中
     *
     * @return static
     */
    public function eager()
    {
        return new static($this->all());
    }

    /**
     * Cache values as they're enumerated.
	 * 在枚举值时缓存它们
     *
     * @return static
     */
    public function remember()
    {
        $iterator = $this->getIterator();

        $iteratorIndex = 0;

        $cache = [];

        return new static(function () use ($iterator, &$iteratorIndex, &$cache) {
            for ($index = 0; true; $index++) {
                if (array_key_exists($index, $cache)) {
                    yield $cache[$index][0] => $cache[$index][1];

                    continue;
                }

                if ($iteratorIndex < $index) {
                    $iterator->next();

                    $iteratorIndex++;
                }

                if (! $iterator->valid()) {
                    break;
                }

                $cache[$index] = [$iterator->key(), $iterator->current()];

                yield $cache[$index][0] => $cache[$index][1];
            }
        });
    }

    /**
     * Get the average value of a given key.
	 * 得到给定键的平均值
     *
     * @param  callable|string|null  $callback
     * @return mixed
     */
    public function avg($callback = null)
    {
        return $this->collect()->avg($callback);
    }

    /**
     * Get the median of a given key.
	 * 求给定键的中值
     *
     * @param  string|array|null  $key
     * @return mixed
     */
    public function median($key = null)
    {
        return $this->collect()->median($key);
    }

    /**
     * Get the mode of a given key.
	 * 得到给定键的模式
     *
     * @param  string|array|null  $key
     * @return array|null
     */
    public function mode($key = null)
    {
        return $this->collect()->mode($key);
    }

    /**
     * Collapse the collection of items into a single array.
	 * 折叠项目集合成单个数组
     *
     * @return static
     */
    public function collapse()
    {
        return new static(function () {
            foreach ($this as $values) {
                if (is_array($values) || $values instanceof Enumerable) {
                    foreach ($values as $value) {
                        yield $value;
                    }
                }
            }
        });
    }

    /**
     * Determine if an item exists in the enumerable.
	 * 确定枚举中是否存在项
     *
     * @param  mixed  $key
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return bool
     */
    public function contains($key, $operator = null, $value = null)
    {
        if (func_num_args() === 1 && $this->useAsCallable($key)) {
            $placeholder = new stdClass;

            return $this->first($key, $placeholder) !== $placeholder;
        }

        if (func_num_args() === 1) {
            $needle = $key;

            foreach ($this as $value) {
                if ($value == $needle) {
                    return true;
                }
            }

            return false;
        }

        return $this->contains($this->operatorForWhere(...func_get_args()));
    }

    /**
     * Cross join the given iterables, returning all possible permutations.
	 * 交叉连接给定的可迭代对象，返回所有可能的排列。
     *
     * @param  array  ...$arrays
     * @return static
     */
    public function crossJoin(...$arrays)
    {
        return $this->passthru('crossJoin', func_get_args());
    }

    /**
     * Get the items that are not present in the given items.
	 * 得到在给定项中不存在的项
     *
     * @param  mixed  $items
     * @return static
     */
    public function diff($items)
    {
        return $this->passthru('diff', func_get_args());
    }

    /**
     * Get the items that are not present in the given items, using the callback.
	 * 得到给定项中不存在的项使用回调
     *
     * @param  mixed  $items
     * @param  callable  $callback
     * @return static
     */
    public function diffUsing($items, callable $callback)
    {
        return $this->passthru('diffUsing', func_get_args());
    }

    /**
     * Get the items whose keys and values are not present in the given items.
	 * 得到其键和值未出现在给定项中的项
     *
     * @param  mixed  $items
     * @return static
     */
    public function diffAssoc($items)
    {
        return $this->passthru('diffAssoc', func_get_args());
    }

    /**
     * Get the items whose keys and values are not present in the given items, using the callback.
	 * 得到其键和值未出现在给定项中的项，使用回调。
     *
     * @param  mixed  $items
     * @param  callable  $callback
     * @return static
     */
    public function diffAssocUsing($items, callable $callback)
    {
        return $this->passthru('diffAssocUsing', func_get_args());
    }

    /**
     * Get the items whose keys are not present in the given items.
	 * 得到键不存在于给定项中的项
     *
     * @param  mixed  $items
     * @return static
     */
    public function diffKeys($items)
    {
        return $this->passthru('diffKeys', func_get_args());
    }

    /**
     * Get the items whose keys are not present in the given items, using the callback.
	 * 得到键不在给定项中的项，使用回调。
     *
     * @param  mixed  $items
     * @param  callable  $callback
     * @return static
     */
    public function diffKeysUsing($items, callable $callback)
    {
        return $this->passthru('diffKeysUsing', func_get_args());
    }

    /**
     * Retrieve duplicate items.
	 * 检索重复项
     *
     * @param  callable|null  $callback
     * @param  bool  $strict
     * @return static
     */
    public function duplicates($callback = null, $strict = false)
    {
        return $this->passthru('duplicates', func_get_args());
    }

    /**
     * Retrieve duplicate items using strict comparison.
	 * 检索重复项使用严格比较
     *
     * @param  callable|null  $callback
     * @return static
     */
    public function duplicatesStrict($callback = null)
    {
        return $this->passthru('duplicatesStrict', func_get_args());
    }

    /**
     * Get all items except for those with the specified keys.
	 * 得到除具有指定键的项之外的所有项
     *
     * @param  mixed  $keys
     * @return static
     */
    public function except($keys)
    {
        return $this->passthru('except', func_get_args());
    }

    /**
     * Run a filter over each of the items.
	 * 运行一个过滤器对每个项目
     *
     * @param  callable|null  $callback
     * @return static
     */
    public function filter(callable $callback = null)
    {
        if (is_null($callback)) {
            $callback = function ($value) {
                return (bool) $value;
            };
        }

        return new static(function () use ($callback) {
            foreach ($this as $key => $value) {
                if ($callback($value, $key)) {
                    yield $key => $value;
                }
            }
        });
    }

    /**
     * Get the first item from the enumerable passing the given truth test.
	 * 得到第一项从通过给定真值测试的枚举中
     *
     * @param  callable|null  $callback
     * @param  mixed  $default
     * @return mixed
     */
    public function first(callable $callback = null, $default = null)
    {
        $iterator = $this->getIterator();

        if (is_null($callback)) {
            if (! $iterator->valid()) {
                return value($default);
            }

            return $iterator->current();
        }

        foreach ($iterator as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return value($default);
    }

    /**
     * Get a flattened list of the items in the collection.
	 * 得到集合中项目的扁平列表
     *
     * @param  int  $depth
     * @return static
     */
    public function flatten($depth = INF)
    {
        $instance = new static(function () use ($depth) {
            foreach ($this as $item) {
                if (! is_array($item) && ! $item instanceof Enumerable) {
                    yield $item;
                } elseif ($depth === 1) {
                    yield from $item;
                } else {
                    yield from (new static($item))->flatten($depth - 1);
                }
            }
        });

        return $instance->values();
    }

    /**
     * Flip the items in the collection.
	 * 翻转集合中的项目
     *
     * @return static
     */
    public function flip()
    {
        return new static(function () {
            foreach ($this as $key => $value) {
                yield $value => $key;
            }
        });
    }

    /**
     * Get an item by key.
	 * 按键获取项目
     *
     * @param  mixed  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (is_null($key)) {
            return;
        }

        foreach ($this as $outerKey => $outerValue) {
            if ($outerKey == $key) {
                return $outerValue;
            }
        }

        return value($default);
    }

    /**
     * Group an associative array by a field or using a callback.
	 * 对关联数组进行分组按字段或使用回调
     *
     * @param  array|callable|string  $groupBy
     * @param  bool  $preserveKeys
     * @return static
     */
    public function groupBy($groupBy, $preserveKeys = false)
    {
        return $this->passthru('groupBy', func_get_args());
    }

    /**
     * Key an associative array by a field or using a callback.
	 * 为关联数组设置键通过字段或使用回调
     *
     * @param  callable|string  $keyBy
     * @return static
     */
    public function keyBy($keyBy)
    {
        return new static(function () use ($keyBy) {
            $keyBy = $this->valueRetriever($keyBy);

            foreach ($this as $key => $item) {
                $resolvedKey = $keyBy($item, $key);

                if (is_object($resolvedKey)) {
                    $resolvedKey = (string) $resolvedKey;
                }

                yield $resolvedKey => $item;
            }
        });
    }

    /**
     * Determine if an item exists in the collection by key.
	 * 确定集合中是否存在项根据键
     *
     * @param  mixed  $key
     * @return bool
     */
    public function has($key)
    {
        $keys = array_flip(is_array($key) ? $key : func_get_args());
        $count = count($keys);

        foreach ($this as $key => $value) {
            if (array_key_exists($key, $keys) && --$count == 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Concatenate values of a given key as a string.
	 * 将给定键的值连接为字符串
     *
     * @param  string  $value
     * @param  string  $glue
     * @return string
     */
    public function implode($value, $glue = null)
    {
        return $this->collect()->implode(...func_get_args());
    }

    /**
     * Intersect the collection with the given items.
	 * 相交集合与给定的项目
     *
     * @param  mixed  $items
     * @return static
     */
    public function intersect($items)
    {
        return $this->passthru('intersect', func_get_args());
    }

    /**
     * Intersect the collection with the given items by key.
	 * 相交集合与给定的项目通过键
     *
     * @param  mixed  $items
     * @return static
     */
    public function intersectByKeys($items)
    {
        return $this->passthru('intersectByKeys', func_get_args());
    }

    /**
     * Determine if the items is empty or not.
	 * 确定项是否为空
     *
     * @return bool
     */
    public function isEmpty()
    {
        return ! $this->getIterator()->valid();
    }

    /**
     * Join all items from the collection using a string. The final items can use a separate glue string.
	 * 使用字符串连接集合中的所有项。最后的项目可以使用一个单独的胶水线。
     *
     * @param  string  $glue
     * @param  string  $finalGlue
     * @return string
     */
    public function join($glue, $finalGlue = '')
    {
        return $this->collect()->join(...func_get_args());
    }

    /**
     * Get the keys of the collection items.
	 * 得到收集项目的键
     *
     * @return static
     */
    public function keys()
    {
        return new static(function () {
            foreach ($this as $key => $value) {
                yield $key;
            }
        });
    }

    /**
     * Get the last item from the collection.
	 * 得到最后一项从集合中
     *
     * @param  callable|null  $callback
     * @param  mixed  $default
     * @return mixed
     */
    public function last(callable $callback = null, $default = null)
    {
        $needle = $placeholder = new stdClass;

        foreach ($this as $key => $value) {
            if (is_null($callback) || $callback($value, $key)) {
                $needle = $value;
            }
        }

        return $needle === $placeholder ? value($default) : $needle;
    }

    /**
     * Get the values of a given key.
	 * 得到给定键的值
     *
     * @param  string|array  $value
     * @param  string|null  $key
     * @return static
     */
    public function pluck($value, $key = null)
    {
        return new static(function () use ($value, $key) {
            [$value, $key] = $this->explodePluckParameters($value, $key);

            foreach ($this as $item) {
                $itemValue = data_get($item, $value);

                if (is_null($key)) {
                    yield $itemValue;
                } else {
                    $itemKey = data_get($item, $key);

                    if (is_object($itemKey) && method_exists($itemKey, '__toString')) {
                        $itemKey = (string) $itemKey;
                    }

                    yield $itemKey => $itemValue;
                }
            }
        });
    }

    /**
     * Run a map over each of the items.
	 * 运行一张地图在每个项目上
     *
     * @param  callable  $callback
     * @return static
     */
    public function map(callable $callback)
    {
        return new static(function () use ($callback) {
            foreach ($this as $key => $value) {
                yield $key => $callback($value, $key);
            }
        });
    }

    /**
     * Run a dictionary map over the items.
	 * 运行字典映射在条目上
     *
     * The callback should return an associative array with a single key/value pair.
     *
     * @param  callable  $callback
     * @return static
     */
    public function mapToDictionary(callable $callback)
    {
        return $this->passthru('mapToDictionary', func_get_args());
    }

    /**
     * Run an associative map over each of the items.
	 * 运行一个关联映射在每个项目上
     *
     * The callback should return an associative array with a single key/value pair.
     *
     * @param  callable  $callback
     * @return static
     */
    public function mapWithKeys(callable $callback)
    {
        return new static(function () use ($callback) {
            foreach ($this as $key => $value) {
                yield from $callback($value, $key);
            }
        });
    }

    /**
     * Merge the collection with the given items.
	 * 合并集合与给定的项
     *
     * @param  mixed  $items
     * @return static
     */
    public function merge($items)
    {
        return $this->passthru('merge', func_get_args());
    }

    /**
     * Recursively merge the collection with the given items.
	 * 递归地合并集合与给定项
     *
     * @param  mixed  $items
     * @return static
     */
    public function mergeRecursive($items)
    {
        return $this->passthru('mergeRecursive', func_get_args());
    }

    /**
     * Create a collection by using this collection for keys and another for its values.
	 * 创建一个集合，将这个集合用于键，另一个用于它的值。
     *
     * @param  mixed  $values
     * @return static
     */
    public function combine($values)
    {
        return new static(function () use ($values) {
            $values = $this->makeIterator($values);

            $errorMessage = 'Both parameters should have an equal number of elements';

            foreach ($this as $key) {
                if (! $values->valid()) {
                    trigger_error($errorMessage, E_USER_WARNING);

                    break;
                }

                yield $key => $values->current();

                $values->next();
            }

            if ($values->valid()) {
                trigger_error($errorMessage, E_USER_WARNING);
            }
        });
    }

    /**
     * Union the collection with the given items.
	 * 联合集合与给定项
     *
     * @param  mixed  $items
     * @return static
     */
    public function union($items)
    {
        return $this->passthru('union', func_get_args());
    }

    /**
     * Create a new collection consisting of every n-th element.
	 * 创建一个包含每n个元素的新集合
     *
     * @param  int  $step
     * @param  int  $offset
     * @return static
     */
    public function nth($step, $offset = 0)
    {
        return new static(function () use ($step, $offset) {
            $position = 0;

            foreach ($this as $item) {
                if ($position % $step === $offset) {
                    yield $item;
                }

                $position++;
            }
        });
    }

    /**
     * Get the items with the specified keys.
	 * 得到具有指定键的项
     *
     * @param  mixed  $keys
     * @return static
     */
    public function only($keys)
    {
        if ($keys instanceof Enumerable) {
            $keys = $keys->all();
        } elseif (! is_null($keys)) {
            $keys = is_array($keys) ? $keys : func_get_args();
        }

        return new static(function () use ($keys) {
            if (is_null($keys)) {
                yield from $this;
            } else {
                $keys = array_flip($keys);

                foreach ($this as $key => $value) {
                    if (array_key_exists($key, $keys)) {
                        yield $key => $value;

                        unset($keys[$key]);

                        if (empty($keys)) {
                            break;
                        }
                    }
                }
            }
        });
    }

    /**
     * Push all of the given items onto the collection.
	 * 推入所有给定的项至集合
     *
     * @param  iterable  $source
     * @return static
     */
    public function concat($source)
    {
        return (new static(function () use ($source) {
            yield from $this;
            yield from $source;
        }))->values();
    }

    /**
     * Get one or a specified number of items randomly from the collection.
	 * 得到一个或指定数量的项随机从集合中
     *
     * @param  int|null  $number
     * @return static|mixed
     *
     * @throws \InvalidArgumentException
     */
    public function random($number = null)
    {
        $result = $this->collect()->random(...func_get_args());

        return is_null($number) ? $result : new static($result);
    }

    /**
     * Reduce the collection to a single value.
	 * 减少集合为单个值
     *
     * @param  callable  $callback
     * @param  mixed  $initial
     * @return mixed
     */
    public function reduce(callable $callback, $initial = null)
    {
        $result = $initial;

        foreach ($this as $value) {
            $result = $callback($result, $value);
        }

        return $result;
    }

    /**
     * Replace the collection items with the given items.
	 * 替换集合项用给定的项
     *
     * @param  mixed  $items
     * @return static
     */
    public function replace($items)
    {
        return new static(function () use ($items) {
            $items = $this->getArrayableItems($items);

            foreach ($this as $key => $value) {
                if (array_key_exists($key, $items)) {
                    yield $key => $items[$key];

                    unset($items[$key]);
                } else {
                    yield $key => $value;
                }
            }

            foreach ($items as $key => $value) {
                yield $key => $value;
            }
        });
    }

    /**
     * Recursively replace the collection items with the given items.
	 * 递归地替换集合项为给定项
     *
     * @param  mixed  $items
     * @return static
     */
    public function replaceRecursive($items)
    {
        return $this->passthru('replaceRecursive', func_get_args());
    }

    /**
     * Reverse items order.
	 * 颠倒项目顺序
     *
     * @return static
     */
    public function reverse()
    {
        return $this->passthru('reverse', func_get_args());
    }

    /**
     * Search the collection for a given value and return the corresponding key if successful.
	 * 在集合中搜索给定的值，如果成功则返回相应的键。
     *
     * @param  mixed  $value
     * @param  bool  $strict
     * @return mixed
     */
    public function search($value, $strict = false)
    {
        $predicate = $this->useAsCallable($value)
            ? $value
            : function ($item) use ($value, $strict) {
                return $strict ? $item === $value : $item == $value;
            };

        foreach ($this as $key => $item) {
            if ($predicate($item, $key)) {
                return $key;
            }
        }

        return false;
    }

    /**
     * Shuffle the items in the collection.
	 * 对集合中的项进行洗牌
     *
     * @param  int  $seed
     * @return static
     */
    public function shuffle($seed = null)
    {
        return $this->passthru('shuffle', func_get_args());
    }

    /**
     * Skip the first {$count} items.
	 * 跳过第一个{$count}项
     *
     * @param  int  $count
     * @return static
     */
    public function skip($count)
    {
        return new static(function () use ($count) {
            $iterator = $this->getIterator();

            while ($iterator->valid() && $count--) {
                $iterator->next();
            }

            while ($iterator->valid()) {
                yield $iterator->key() => $iterator->current();

                $iterator->next();
            }
        });
    }

    /**
     * Get a slice of items from the enumerable.
	 * 得到项目的切片从可枚举对象中
     *
     * @param  int  $offset
     * @param  int  $length
     * @return static
     */
    public function slice($offset, $length = null)
    {
        if ($offset < 0 || $length < 0) {
            return $this->passthru('slice', func_get_args());
        }

        $instance = $this->skip($offset);

        return is_null($length) ? $instance : $instance->take($length);
    }

    /**
     * Split a collection into a certain number of groups.
	 * 将一个集合分成一定数量的组
     *
     * @param  int  $numberOfGroups
     * @return static
     */
    public function split($numberOfGroups)
    {
        return $this->passthru('split', func_get_args());
    }

    /**
     * Chunk the collection into chunks of the given size.
	 * 将集合分成给定大小的块
     *
     * @param  int  $size
     * @return static
     */
    public function chunk($size)
    {
        if ($size <= 0) {
            return static::empty();
        }

        return new static(function () use ($size) {
            $iterator = $this->getIterator();

            while ($iterator->valid()) {
                $chunk = [];

                while (true) {
                    $chunk[$iterator->key()] = $iterator->current();

                    if (count($chunk) < $size) {
                        $iterator->next();

                        if (! $iterator->valid()) {
                            break;
                        }
                    } else {
                        break;
                    }
                }

                yield new static($chunk);

                $iterator->next();
            }
        });
    }

    /**
     * Sort through each item with a callback.
	 * 排序每个项目使用回调
     *
     * @param  callable|null  $callback
     * @return static
     */
    public function sort(callable $callback = null)
    {
        return $this->passthru('sort', func_get_args());
    }

    /**
     * Sort the collection using the given callback.
	 * 排序集合使用给定回调
     *
     * @param  callable|string  $callback
     * @param  int  $options
     * @param  bool  $descending
     * @return static
     */
    public function sortBy($callback, $options = SORT_REGULAR, $descending = false)
    {
        return $this->passthru('sortBy', func_get_args());
    }

    /**
     * Sort the collection in descending order using the given callback.
	 * 按降序对集合进行排序使用给定的回调
     *
     * @param  callable|string  $callback
     * @param  int  $options
     * @return static
     */
    public function sortByDesc($callback, $options = SORT_REGULAR)
    {
        return $this->passthru('sortByDesc', func_get_args());
    }

    /**
     * Sort the collection keys.
	 * 对集合键排序
     *
     * @param  int  $options
     * @param  bool  $descending
     * @return static
     */
    public function sortKeys($options = SORT_REGULAR, $descending = false)
    {
        return $this->passthru('sortKeys', func_get_args());
    }

    /**
     * Sort the collection keys in descending order.
	 * 降序排序集合键进行
     *
     * @param  int  $options
     * @return static
     */
    public function sortKeysDesc($options = SORT_REGULAR)
    {
        return $this->passthru('sortKeysDesc', func_get_args());
    }

    /**
     * Take the first or last {$limit} items.
	 * 取第一个或最后一个{$limit}项
     *
     * @param  int  $limit
     * @return static
     */
    public function take($limit)
    {
        if ($limit < 0) {
            return $this->passthru('take', func_get_args());
        }

        return new static(function () use ($limit) {
            $iterator = $this->getIterator();

            while ($limit--) {
                if (! $iterator->valid()) {
                    break;
                }

                yield $iterator->key() => $iterator->current();

                if ($limit) {
                    $iterator->next();
                }
            }
        });
    }

    /**
     * Pass each item in the collection to the given callback, lazily.
	 * 惰性地将集合中的每个项传递给给定的回调函数
     *
     * @param  callable  $callback
     * @return static
     */
    public function tapEach(callable $callback)
    {
        return new static(function () use ($callback) {
            foreach ($this as $key => $value) {
                $callback($value, $key);

                yield $key => $value;
            }
        });
    }

    /**
     * Reset the keys on the underlying array.
	 * 重置基础数组上的键
     *
     * @return static
     */
    public function values()
    {
        return new static(function () {
            foreach ($this as $item) {
                yield $item;
            }
        });
    }

    /**
     * Zip the collection together with one or more arrays.
	 * 将集合与一个或多个数组压缩在一起
     *
     * e.g. new LazyCollection([1, 2, 3])->zip([4, 5, 6]);
     *      => [[1, 4], [2, 5], [3, 6]]
     *
     * @param  mixed  ...$items
     * @return static
     */
    public function zip($items)
    {
        $iterables = func_get_args();

        return new static(function () use ($iterables) {
            $iterators = Collection::make($iterables)->map(function ($iterable) {
                return $this->makeIterator($iterable);
            })->prepend($this->getIterator());

            while ($iterators->contains->valid()) {
                yield new static($iterators->map->current());

                $iterators->each->next();
            }
        });
    }

    /**
     * Pad collection to the specified length with a value.
	 * 使用值将集合垫到指定的长度
     *
     * @param  int  $size
     * @param  mixed  $value
     * @return static
     */
    public function pad($size, $value)
    {
        if ($size < 0) {
            return $this->passthru('pad', func_get_args());
        }

        return new static(function () use ($size, $value) {
            $yielded = 0;

            foreach ($this as $index => $item) {
                yield $index => $item;

                $yielded++;
            }

            while ($yielded++ < $size) {
                yield $value;
            }
        });
    }

    /**
     * Get the values iterator.
	 * 得到值迭代器
     *
     * @return \Traversable
     */
    public function getIterator()
    {
        return $this->makeIterator($this->source);
    }

    /**
     * Count the number of items in the collection.
	 * 计算集合中的项数
     *
     * @return int
     */
    public function count()
    {
        if (is_array($this->source)) {
            return count($this->source);
        }

        return iterator_count($this->getIterator());
    }

    /**
     * Make an iterator from the given source.
	 * 创建一个迭代器从给定的源代码
     *
     * @param  mixed  $source
     * @return \Traversable
     */
    protected function makeIterator($source)
    {
        if ($source instanceof IteratorAggregate) {
            return $source->getIterator();
        }

        if (is_array($source)) {
            return new ArrayIterator($source);
        }

        return $source();
    }

    /**
     * Explode the "value" and "key" arguments passed to "pluck".
	 * 爆炸传递给"pluck"的"value"和"key"参数
     *
     * @param  string|array  $value
     * @param  string|array|null  $key
     * @return array
     */
    protected function explodePluckParameters($value, $key)
    {
        $value = is_string($value) ? explode('.', $value) : $value;

        $key = is_null($key) || is_array($key) ? $key : explode('.', $key);

        return [$value, $key];
    }

    /**
     * Pass this lazy collection through a method on the collection class.
	 * 通过集合类上的方法传递此惰性集合
     *
     * @param  string  $method
     * @param  array  $params
     * @return static
     */
    protected function passthru($method, array $params)
    {
        return new static(function () use ($method, $params) {
            yield from $this->collect()->$method(...$params);
        });
    }
}
