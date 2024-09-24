<?php
/**
 * 支持，可枚举
 */

namespace Illuminate\Support;

use Countable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use IteratorAggregate;
use JsonSerializable;

interface Enumerable extends Arrayable, Countable, IteratorAggregate, Jsonable, JsonSerializable
{
    /**
     * Create a new collection instance if the value isn't one already.
	 * 创建新的集合实例，如果该值还没有
     *
     * @param  mixed  $items
     * @return static
     */
    public static function make($items = []);

    /**
     * Create a new instance by invoking the callback a given amount of times.
	 * 通过调用给定次数的回调来创建一个新实例
     *
     * @param  int  $number
     * @param  callable  $callback
     * @return static
     */
    public static function times($number, callable $callback = null);

    /**
     * Wrap the given value in a collection if applicable.
	 * 如果适用，将给定值包装在集合中。
     *
     * @param  mixed  $value
     * @return static
     */
    public static function wrap($value);

    /**
     * Get the underlying items from the given collection if applicable.
	 * 得到基础项(如果适用)从给定集合中
     *
     * @param  array|static  $value
     * @return array
     */
    public static function unwrap($value);

    /**
     * Get all items in the enumerable.
	 * 得到枚举中的所有项
     *
     * @return array
     */
    public function all();

    /**
     * Alias for the "avg" method.
	 * "avg"方法的别名
     *
     * @param  callable|string|null  $callback
     * @return mixed
     */
    public function average($callback = null);

    /**
     * Get the median of a given key.
	 * 求给定键的中值
     *
     * @param  string|array|null  $key
     * @return mixed
     */
    public function median($key = null);

    /**
     * Get the mode of a given key.
	 * 得到给定键的模式
     *
     * @param  string|array|null  $key
     * @return array|null
     */
    public function mode($key = null);

    /**
     * Collapse the items into a single enumerable.
	 * 将这些项折叠成单个枚举
     *
     * @return static
     */
    public function collapse();

    /**
     * Alias for the "contains" method.
	 * "contains"方法的别名
     *
     * @param  mixed  $key
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return bool
     */
    public function some($key, $operator = null, $value = null);

    /**
     * Determine if an item exists, using strict comparison.
	 * 确定项是否存在使用严格比较
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @return bool
     */
    public function containsStrict($key, $value = null);

    /**
     * Get the average value of a given key.
	 * 得到给定键的平均值
     *
     * @param  callable|string|null  $callback
     * @return mixed
     */
    public function avg($callback = null);

    /**
     * Determine if an item exists in the enumerable.
	 * 确定枚举中是否存在项
     *
     * @param  mixed  $key
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return bool
     */
    public function contains($key, $operator = null, $value = null);

    /**
     * Dump the collection and end the script.
	 * 转储集合并结束脚本
     *
     * @param  mixed  ...$args
     * @return void
     */
    public function dd(...$args);

    /**
     * Dump the collection.
	 * 转储集合
     *
     * @return $this
     */
    public function dump();

    /**
     * Get the items that are not present in the given items.
	 * 得到在给定项中不存在的项
     *
     * @param  mixed  $items
     * @return static
     */
    public function diff($items);

    /**
     * Get the items that are not present in the given items, using the callback.
	 * 得到给定项中不存在的项使用回调
     *
     * @param  mixed  $items
     * @param  callable  $callback
     * @return static
     */
    public function diffUsing($items, callable $callback);

    /**
     * Get the items whose keys and values are not present in the given items.
	 * 得到在给定项中不存在键和值的项
     *
     * @param  mixed  $items
     * @return static
     */
    public function diffAssoc($items);

    /**
     * Get the items whose keys and values are not present in the given items, using the callback.
	 * 得到其键和值未出现在给定项中的项使用回调
     *
     * @param  mixed  $items
     * @param  callable  $callback
     * @return static
     */
    public function diffAssocUsing($items, callable $callback);

    /**
     * Get the items whose keys are not present in the given items.
	 * 得到键不存在于给定项中的项
     *
     * @param  mixed  $items
     * @return static
     */
    public function diffKeys($items);

    /**
     * Get the items whose keys are not present in the given items, using the callback.
	 * 得到键不在给定项中的项使用回调
     *
     * @param  mixed  $items
     * @param  callable  $callback
     * @return static
     */
    public function diffKeysUsing($items, callable $callback);

    /**
     * Retrieve duplicate items.
	 * 检索重复项
     *
     * @param  callable|null  $callback
     * @param  bool  $strict
     * @return static
     */
    public function duplicates($callback = null, $strict = false);

    /**
     * Retrieve duplicate items using strict comparison.
	 * 检索重复项使用严格比较
     *
     * @param  callable|null  $callback
     * @return static
     */
    public function duplicatesStrict($callback = null);

    /**
     * Execute a callback over each item.
	 * 执行回调对每个项目
     *
     * @param  callable  $callback
     * @return $this
     */
    public function each(callable $callback);

    /**
     * Execute a callback over each nested chunk of items.
	 * 执行回调对每个嵌套的项块
     *
     * @param  callable  $callback
     * @return static
     */
    public function eachSpread(callable $callback);

    /**
     * Determine if all items pass the given truth test.
	 * 确定是否所有项目都通过给定的真值测试
     *
     * @param  string|callable  $key
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return bool
     */
    public function every($key, $operator = null, $value = null);

    /**
     * Get all items except for those with the specified keys.
	 * 得到除具有指定键的项之外的所有项
     *
     * @param  mixed  $keys
     * @return static
     */
    public function except($keys);

    /**
     * Run a filter over each of the items.
	 * 运行一个过滤器对每个项目
     *
     * @param  callable|null  $callback
     * @return static
     */
    public function filter(callable $callback = null);

    /**
     * Apply the callback if the value is truthy.
	 * 应用回调如果值为真
     *
     * @param  bool  $value
     * @param  callable  $callback
     * @param  callable  $default
     * @return static|mixed
     */
    public function when($value, callable $callback, callable $default = null);

    /**
     * Apply the callback if the collection is empty.
	 * 如果集合为空，则应用回调。
     *
     * @param  callable  $callback
     * @param  callable  $default
     * @return static|mixed
     */
    public function whenEmpty(callable $callback, callable $default = null);

    /**
     * Apply the callback if the collection is not empty.
	 * 应用回调如果集合不为空
     *
     * @param  callable  $callback
     * @param  callable  $default
     * @return static|mixed
     */
    public function whenNotEmpty(callable $callback, callable $default = null);

    /**
     * Apply the callback if the value is falsy.
	 * 应用回调如果值为假值
     *
     * @param  bool  $value
     * @param  callable  $callback
     * @param  callable  $default
     * @return static|mixed
     */
    public function unless($value, callable $callback, callable $default = null);

    /**
     * Apply the callback unless the collection is empty.
	 * 应用回调除非集合为空
     *
     * @param  callable  $callback
     * @param  callable  $default
     * @return static|mixed
     */
    public function unlessEmpty(callable $callback, callable $default = null);

    /**
     * Apply the callback unless the collection is not empty.
	 * 应用回调除非集合不为空
     *
     * @param  callable  $callback
     * @param  callable  $default
     * @return static|mixed
     */
    public function unlessNotEmpty(callable $callback, callable $default = null);

    /**
     * Filter items by the given key value pair.
	 * 筛选项根据给定的键值对
     *
     * @param  string  $key
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return static
     */
    public function where($key, $operator = null, $value = null);

    /**
     * Filter items by the given key value pair using strict comparison.
	 * 按给定的键值对筛选项使用严格比较
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return static
     */
    public function whereStrict($key, $value);

    /**
     * Filter items by the given key value pair.
	 * 筛选项根据给定的键值对
     *
     * @param  string  $key
     * @param  mixed  $values
     * @param  bool  $strict
     * @return static
     */
    public function whereIn($key, $values, $strict = false);

    /**
     * Filter items by the given key value pair using strict comparison.
	 * 按给定键值对筛选项使用严格比较
     *
     * @param  string  $key
     * @param  mixed  $values
     * @return static
     */
    public function whereInStrict($key, $values);

    /**
     * Filter items such that the value of the given key is between the given values.
	 * 筛选项，使给定键的值在给定值之间。
     *
     * @param  string  $key
     * @param  array  $values
     * @return static
     */
    public function whereBetween($key, $values);

    /**
     * Filter items such that the value of the given key is not between the given values.
	 * 筛选项，使给定键的值不在给定值之间。
     *
     * @param  string  $key
     * @param  array  $values
     * @return static
     */
    public function whereNotBetween($key, $values);

    /**
     * Filter items by the given key value pair.
	 * 筛选项根据给定的键值对
     *
     * @param  string  $key
     * @param  mixed  $values
     * @param  bool  $strict
     * @return static
     */
    public function whereNotIn($key, $values, $strict = false);

    /**
     * Filter items by the given key value pair using strict comparison.
	 * 筛选项根据给定的键值对使用严格比较
     *
     * @param  string  $key
     * @param  mixed  $values
     * @return static
     */
    public function whereNotInStrict($key, $values);

    /**
     * Filter the items, removing any items that don't match the given type.
	 * 筛选项目，删除与给定类型不匹配的任何项目。
     *
     * @param  string  $type
     * @return static
     */
    public function whereInstanceOf($type);

    /**
     * Get the first item from the enumerable passing the given truth test.
	 * 得到第一项从通过给定真值测试的枚举中
     *
     * @param  callable|null  $callback
     * @param  mixed  $default
     * @return mixed
     */
    public function first(callable $callback = null, $default = null);

    /**
     * Get the first item by the given key value pair.
	 * 得到第一项根据给定的键值对
     *
     * @param  string  $key
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return mixed
     */
    public function firstWhere($key, $operator = null, $value = null);

    /**
     * Flip the values with their keys.
	 * 用键翻转值
     *
     * @return static
     */
    public function flip();

    /**
     * Get an item from the collection by key.
	 * 得到项目按键从集合中
     *
     * @param  mixed  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null);

    /**
     * Group an associative array by a field or using a callback.
	 * 按字段或使用回调对关联数组进行分组
     *
     * @param  array|callable|string  $groupBy
     * @param  bool  $preserveKeys
     * @return static
     */
    public function groupBy($groupBy, $preserveKeys = false);

    /**
     * Key an associative array by a field or using a callback.
	 * 通过字段或使用回调为关联数组设置键
     *
     * @param  callable|string  $keyBy
     * @return static
     */
    public function keyBy($keyBy);

    /**
     * Determine if an item exists in the collection by key.
	 * 确定集合中是否存在项根据键
     *
     * @param  mixed  $key
     * @return bool
     */
    public function has($key);

    /**
     * Concatenate values of a given key as a string.
	 * 连接给定键的值为字符串
     *
     * @param  string  $value
     * @param  string  $glue
     * @return string
     */
    public function implode($value, $glue = null);

    /**
     * Intersect the collection with the given items.
	 * 将集合与给定的项目相交
     *
     * @param  mixed  $items
     * @return static
     */
    public function intersect($items);

    /**
     * Intersect the collection with the given items by key.
	 * 通过键将集合与给定的项目相交
     *
     * @param  mixed  $items
     * @return static
     */
    public function intersectByKeys($items);

    /**
     * Determine if the collection is empty or not.
	 * 确定集合是否为空
     *
     * @return bool
     */
    public function isEmpty();

    /**
     * Determine if the collection is not empty.
	 * 确定集合是否不为空
     *
     * @return bool
     */
    public function isNotEmpty();

    /**
     * Join all items from the collection using a string. The final items can use a separate glue string.
	 * 使用字符串连接集合中的所有项。最后的项目可以使用一个单独的胶水线。
     *
     * @param  string  $glue
     * @param  string  $finalGlue
     * @return string
     */
    public function join($glue, $finalGlue = '');

    /**
     * Get the keys of the collection items.
	 * 得到收集项目的密钥
     *
     * @return static
     */
    public function keys();

    /**
     * Get the last item from the collection.
	 * 得到最后一项从集合中
     *
     * @param  callable|null  $callback
     * @param  mixed  $default
     * @return mixed
     */
    public function last(callable $callback = null, $default = null);

    /**
     * Run a map over each of the items.
	 * 在每个项目上运行一张地图
     *
     * @param  callable  $callback
     * @return static
     */
    public function map(callable $callback);

    /**
     * Run a map over each nested chunk of items.
	 * 运行一个映射在每个嵌套的项目块上
     *
     * @param  callable  $callback
     * @return static
     */
    public function mapSpread(callable $callback);

    /**
     * Run a dictionary map over the items.
	 * 运行字典映射在条目上
     *
     * The callback should return an associative array with a single key/value pair.
     *
     * @param  callable  $callback
     * @return static
     */
    public function mapToDictionary(callable $callback);

    /**
     * Run a grouping map over the items.
	 * 运行分组映射在项目上
     *
     * The callback should return an associative array with a single key/value pair.
     *
     * @param  callable  $callback
     * @return static
     */
    public function mapToGroups(callable $callback);

    /**
     * Run an associative map over each of the items.
	 * 运行一个关联映射在每个项目上
     *
     * The callback should return an associative array with a single key/value pair.
     *
     * @param  callable  $callback
     * @return static
     */
    public function mapWithKeys(callable $callback);

    /**
     * Map a collection and flatten the result by a single level.
	 * 映射一个集合并将结果平铺一个级别
     *
     * @param  callable  $callback
     * @return static
     */
    public function flatMap(callable $callback);

    /**
     * Map the values into a new class.
	 * 映射值到一个新类
     *
     * @param  string  $class
     * @return static
     */
    public function mapInto($class);

    /**
     * Merge the collection with the given items.
	 * 合并集合使用给定的项
     *
     * @param  mixed  $items
     * @return static
     */
    public function merge($items);

    /**
     * Recursively merge the collection with the given items.
	 * 递归地合并集合使用给定的项
     *
     * @param  mixed  $items
     * @return static
     */
    public function mergeRecursive($items);

    /**
     * Create a collection by using this collection for keys and another for its values.
	 * 创建一个集合，将这个集合用于键，另一个用于它的值。
     *
     * @param  mixed  $values
     * @return static
     */
    public function combine($values);

    /**
     * Union the collection with the given items.
	 * 将集合与给定项联合
     *
     * @param  mixed  $items
     * @return static
     */
    public function union($items);

    /**
     * Get the min value of a given key.
	 * 得到给定键的最小值
     *
     * @param  callable|string|null  $callback
     * @return mixed
     */
    public function min($callback = null);

    /**
     * Get the max value of a given key.
	 * 得到给定键的最大值
     *
     * @param  callable|string|null  $callback
     * @return mixed
     */
    public function max($callback = null);

    /**
     * Create a new collection consisting of every n-th element.
	 * 创建一个包含每n个元素的新集合
     *
     * @param  int  $step
     * @param  int  $offset
     * @return static
     */
    public function nth($step, $offset = 0);

    /**
     * Get the items with the specified keys.
	 * 得到具有指定键的项
     *
     * @param  mixed  $keys
     * @return static
     */
    public function only($keys);

    /**
     * "Paginate" the collection by slicing it into a smaller collection.
	 * 通过将集合切片为更小的集合来"分页"集合
     *
     * @param  int  $page
     * @param  int  $perPage
     * @return static
     */
    public function forPage($page, $perPage);

    /**
     * Partition the collection into two arrays using the given callback or key.
	 * 使用给定的回调或键将集合划分为两个数组
     *
     * @param  callable|string  $key
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return static
     */
    public function partition($key, $operator = null, $value = null);

    /**
     * Push all of the given items onto the collection.
	 * 将所有给定的项推入集合
     *
     * @param  iterable  $source
     * @return static
     */
    public function concat($source);

    /**
     * Get one or a specified number of items randomly from the collection.
	 * 得到一个或指定数量的项随机从集合中
     *
     * @param  int|null  $number
     * @return static|mixed
     *
     * @throws \InvalidArgumentException
     */
    public function random($number = null);

    /**
     * Reduce the collection to a single value.
	 * 将集合减少为单个值
     *
     * @param  callable  $callback
     * @param  mixed  $initial
     * @return mixed
     */
    public function reduce(callable $callback, $initial = null);

    /**
     * Replace the collection items with the given items.
	 * 替换集合项用给定的项
     *
     * @param  mixed  $items
     * @return static
     */
    public function replace($items);

    /**
     * Recursively replace the collection items with the given items.
	 * 递归地替换集合项用给定的项
     *
     * @param  mixed  $items
     * @return static
     */
    public function replaceRecursive($items);

    /**
     * Reverse items order.
     *
     * @return static
     */
    public function reverse();

    /**
     * Search the collection for a given value and return the corresponding key if successful.
	 * 在集合中搜索给定的值，如果成功则返回相应的键。
     *
     * @param  mixed  $value
     * @param  bool  $strict
     * @return mixed
     */
    public function search($value, $strict = false);

    /**
     * Shuffle the items in the collection.
	 * 对集合中的项进行洗牌
     *
     * @param  int  $seed
     * @return static
     */
    public function shuffle($seed = null);

    /**
     * Skip the first {$count} items.
	 * 跳过第一个{$count}项
     *
     * @param  int  $count
     * @return static
     */
    public function skip($count);

    /**
     * Get a slice of items from the enumerable.
	 * 得到项目的切片从可枚举对象中
     *
     * @param  int  $offset
     * @param  int  $length
     * @return static
     */
    public function slice($offset, $length = null);

    /**
     * Split a collection into a certain number of groups.
	 * 将一个集合分成一定数量的组
     *
     * @param  int  $numberOfGroups
     * @return static
     */
    public function split($numberOfGroups);

    /**
     * Chunk the collection into chunks of the given size.
	 * 将集合分成给定大小的块
     *
     * @param  int  $size
     * @return static
     */
    public function chunk($size);

    /**
     * Sort through each item with a callback.
	 * 使用回调对每个项目进行排序
     *
     * @param  callable|null  $callback
     * @return static
     */
    public function sort(callable $callback = null);

    /**
     * Sort the collection using the given callback.
	 * 使用给定的回调对集合进行排序
     *
     * @param  callable|string  $callback
     * @param  int  $options
     * @param  bool  $descending
     * @return static
     */
    public function sortBy($callback, $options = SORT_REGULAR, $descending = false);

    /**
     * Sort the collection in descending order using the given callback.
	 * 使用给定的回调按降序对集合进行排序
     *
     * @param  callable|string  $callback
     * @param  int  $options
     * @return static
     */
    public function sortByDesc($callback, $options = SORT_REGULAR);

    /**
     * Sort the collection keys.
	 * 对集合键排序
     *
     * @param  int  $options
     * @param  bool  $descending
     * @return static
     */
    public function sortKeys($options = SORT_REGULAR, $descending = false);

    /**
     * Sort the collection keys in descending order.
	 * 按降序对集合键进行排序
     *
     * @param  int  $options
     * @return static
     */
    public function sortKeysDesc($options = SORT_REGULAR);

    /**
     * Get the sum of the given values.
	 * 得到给定值的和
     *
     * @param  callable|string|null  $callback
     * @return mixed
     */
    public function sum($callback = null);

    /**
     * Take the first or last {$limit} items.
	 * 取第一个或最后一个{$limit}项
     *
     * @param  int  $limit
     * @return static
     */
    public function take($limit);

    /**
     * Pass the collection to the given callback and then return it.
	 * 将集合传递给给定的回调函数，然后返回它。
     *
     * @param  callable  $callback
     * @return $this
     */
    public function tap(callable $callback);

    /**
     * Pass the enumerable to the given callback and return the result.
	 * 传递可枚举对象给给定的回调函数并返回结果
     *
     * @param  callable  $callback
     * @return mixed
     */
    public function pipe(callable $callback);

    /**
     * Get the values of a given key.
	 * 得到给定键的值
     *
     * @param  string|array  $value
     * @param  string|null  $key
     * @return static
     */
    public function pluck($value, $key = null);

    /**
     * Create a collection of all elements that do not pass a given truth test.
	 * 创建一个未通过给定真值测试的所有元素的集合
     *
     * @param  callable|mixed  $callback
     * @return static
     */
    public function reject($callback = true);

    /**
     * Return only unique items from the collection array.
	 * 只返回集合数组中唯一的项
     *
     * @param  string|callable|null  $key
     * @param  bool  $strict
     * @return static
     */
    public function unique($key = null, $strict = false);

    /**
     * Return only unique items from the collection array using strict comparison.
	 * 只返回集合数组中的唯一项使用严格比较
     *
     * @param  string|callable|null  $key
     * @return static
     */
    public function uniqueStrict($key = null);

    /**
     * Reset the keys on the underlying array.
	 * 重置基础数组上的键
     *
     * @return static
     */
    public function values();

    /**
     * Pad collection to the specified length with a value.
	 * 使用值将集合垫到指定的长度
     *
     * @param  int  $size
     * @param  mixed  $value
     * @return static
     */
    public function pad($size, $value);

    /**
     * Count the number of items in the collection using a given truth test.
	 * 使用给定的真值测试计算集合中的项目数量
     *
     * @param  callable|null  $callback
     * @return static
     */
    public function countBy($callback = null);

    /**
     * Collect the values into a collection.
	 * 收集这些值到一个集合中
     *
     * @return \Illuminate\Support\Collection
     */
    public function collect();

    /**
     * Convert the collection to its string representation.
	 * 将集合转换为其字符串表示形式
     *
     * @return string
     */
    public function __toString();

    /**
     * Add a method to the list of proxied methods.
	 * 添加一个方法向代理方法列表中
     *
     * @param  string  $method
     * @return void
     */
    public static function proxy($method);

    /**
     * Dynamically access collection proxies.
	 * 动态访问集合代理
     *
     * @param  string  $key
     * @return mixed
     *
     * @throws \Exception
     */
    public function __get($key);
}
