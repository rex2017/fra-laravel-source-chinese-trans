<?php
/**
 * Redis，Redis连接
 */

namespace Illuminate\Redis\Connections;

use Closure;
use Illuminate\Contracts\Redis\Connection as ConnectionContract;
use Illuminate\Support\Str;
use Redis;
use RedisCluster;
use RedisException;

/**
 * @mixin \Redis
 */
class PhpRedisConnection extends Connection implements ConnectionContract
{
    /**
     * The connection creation callback.
	 * 连接创建回调
     *
     * @var callable
     */
    protected $connector;

    /**
     * The connection configuration array.
	 * 连接配置数组
     *
     * @var array
     */
    protected $config;

    /**
     * Create a new PhpRedis connection.
	 * 创建新的PhpRedis连接
     *
     * @param  \Redis  $client
     * @param  callable|null  $connector
     * @param  array  $config
     * @return void
     */
    public function __construct($client, callable $connector = null, array $config = [])
    {
        $this->client = $client;
        $this->config = $config;
        $this->connector = $connector;
    }

    /**
     * Returns the value of the given key.
	 * 返回给定键的值
     *
     * @param  string  $key
     * @return string|null
     */
    public function get($key)
    {
        $result = $this->command('get', [$key]);

        return $result !== false ? $result : null;
    }

    /**
     * Get the values of all the given keys.
	 * 得到所有给定键的值
     *
     * @param  array  $keys
     * @return array
     */
    public function mget(array $keys)
    {
        return array_map(function ($value) {
            return $value !== false ? $value : null;
        }, $this->command('mget', [$keys]));
    }

    /**
     * Set the string value in argument as value of the key.
	 * 设置参数中的字符串值为键的值
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  string|null  $expireResolution
     * @param  int|null  $expireTTL
     * @param  string|null  $flag
     * @return bool
     */
    public function set($key, $value, $expireResolution = null, $expireTTL = null, $flag = null)
    {
        return $this->command('set', [
            $key,
            $value,
            $expireResolution ? [$flag, $expireResolution => $expireTTL] : null,
        ]);
    }

    /**
     * Set the given key if it doesn't exist.
	 * 设置给定的键如果它不存在
     *
     * @param  string  $key
     * @param  string  $value
     * @return int
     */
    public function setnx($key, $value)
    {
        return (int) $this->command('setnx', [$key, $value]);
    }

    /**
     * Get the value of the given hash fields.
	 * 得到给定哈希字段的值
     *
     * @param  string  $key
     * @param  mixed  $dictionary
     * @return array
     */
    public function hmget($key, ...$dictionary)
    {
        if (count($dictionary) === 1) {
            $dictionary = $dictionary[0];
        }

        return array_values($this->command('hmget', [$key, $dictionary]));
    }

    /**
     * Set the given hash fields to their respective values.
	 * 设置给定的散列字段为各自的值
     *
     * @param  string  $key
     * @param  mixed  $dictionary
     * @return int
     */
    public function hmset($key, ...$dictionary)
    {
        if (count($dictionary) === 1) {
            $dictionary = $dictionary[0];
        } else {
            $input = collect($dictionary);

            $dictionary = $input->nth(2)->combine($input->nth(2, 1))->toArray();
        }

        return $this->command('hmset', [$key, $dictionary]);
    }

    /**
     * Set the given hash field if it doesn't exist.
	 * 设置给定的散列字段如果它不存在
     *
     * @param  string  $hash
     * @param  string  $key
     * @param  string  $value
     * @return int
     */
    public function hsetnx($hash, $key, $value)
    {
        return (int) $this->command('hsetnx', [$hash, $key, $value]);
    }

    /**
     * Removes the first count occurrences of the value element from the list.
	 * 移除value元素的第一个计数出现次数从列表中
     *
     * @param  string  $key
     * @param  int  $count
     * @param  mixed  $value
     * @return int|false
     */
    public function lrem($key, $count, $value)
    {
        return $this->command('lrem', [$key, $value, $count]);
    }

    /**
     * Removes and returns the first element of the list stored at key.
	 * 移除并返回存储在key中的列表的第一个元素
     *
     * @param  mixed  $arguments
     * @return array|null
     */
    public function blpop(...$arguments)
    {
        $result = $this->command('blpop', $arguments);

        return empty($result) ? null : $result;
    }

    /**
     * Removes and returns the last element of the list stored at key.
	 * 删除并返回存储在key处的列表的最后一个元素
     *
     * @param  mixed  $arguments
     * @return array|null
     */
    public function brpop(...$arguments)
    {
        $result = $this->command('brpop', $arguments);

        return empty($result) ? null : $result;
    }

    /**
     * Removes and returns a random element from the set value at key.
	 * 移除并返回一个随机元素从键处的设置值中
     *
     * @param  string  $key
     * @param  int|null  $count
     * @return mixed|false
     */
    public function spop($key, $count = 1)
    {
        return $this->command('spop', func_get_args());
    }

    /**
     * Add one or more members to a sorted set or update its score if it already exists.
	 * 添加一个或多个成员至排序设置或更新其分数，如果它已经存在。
     *
     * @param  string  $key
     * @param  mixed  $dictionary
     * @return int
     */
    public function zadd($key, ...$dictionary)
    {
        if (is_array(end($dictionary))) {
            foreach (array_pop($dictionary) as $member => $score) {
                $dictionary[] = $score;
                $dictionary[] = $member;
            }
        }

        $options = [];

        foreach (array_slice($dictionary, 0, 3) as $i => $value) {
            if (in_array($value, ['nx', 'xx', 'ch', 'incr', 'NX', 'XX', 'CH', 'INCR'], true)) {
                $options[] = $value;

                unset($dictionary[$i]);
            }
        }

        return $this->command('zadd', array_merge([$key], [$options], array_values($dictionary)));
    }

    /**
     * Return elements with score between $min and $max.
	 * 返回得分在$min和$max之间的元素
     *
     * @param  string  $key
     * @param  mixed  $min
     * @param  mixed  $max
     * @param  array  $options
     * @return array
     */
    public function zrangebyscore($key, $min, $max, $options = [])
    {
        if (isset($options['limit'])) {
            $options['limit'] = [
                $options['limit']['offset'],
                $options['limit']['count'],
            ];
        }

        return $this->command('zRangeByScore', [$key, $min, $max, $options]);
    }

    /**
     * Return elements with score between $min and $max.
	 * 返回得分在$min和$max之间的元素
     *
     * @param  string  $key
     * @param  mixed  $min
     * @param  mixed  $max
     * @param  array  $options
     * @return array
     */
    public function zrevrangebyscore($key, $min, $max, $options = [])
    {
        if (isset($options['limit'])) {
            $options['limit'] = [
                $options['limit']['offset'],
                $options['limit']['count'],
            ];
        }

        return $this->command('zRevRangeByScore', [$key, $min, $max, $options]);
    }

    /**
     * Find the intersection between sets and store in a new set.
	 * 找到集合之间的交集并存储在一个新集合中
     *
     * @param  string  $output
     * @param  array  $keys
     * @param  array  $options
     * @return int
     */
    public function zinterstore($output, $keys, $options = [])
    {
        return $this->command('zinterstore', [$output, $keys,
            $options['weights'] ?? null,
            $options['aggregate'] ?? 'sum',
        ]);
    }

    /**
     * Find the union between sets and store in a new set.
	 * 找到集合之间的并集并存储在一个新集合中
     *
     * @param  string  $output
     * @param  array  $keys
     * @param  array  $options
     * @return int
     */
    public function zunionstore($output, $keys, $options = [])
    {
        return $this->command('zunionstore', [$output, $keys,
            $options['weights'] ?? null,
            $options['aggregate'] ?? 'sum',
        ]);
    }

    /**
     * Scans the all keys based on options.
	 * 扫描所有密钥根据选项
     *
     * @param  mixed  $cursor
     * @param  array  $options
     * @return mixed
     */
    public function scan($cursor, $options = [])
    {
        $result = $this->client->scan($cursor,
            $options['match'] ?? '*',
            $options['count'] ?? 10
        );

        return empty($result) ? $result : [$cursor, $result];
    }

    /**
     * Scans the given set for all values based on options.
	 * 扫描给定集合的所有值基于选项
     *
     * @param  string  $key
     * @param  mixed  $cursor
     * @param  array  $options
     * @return mixed
     */
    public function zscan($key, $cursor, $options = [])
    {
        $result = $this->client->zscan($key, $cursor,
            $options['match'] ?? '*',
            $options['count'] ?? 10
        );

        return $result === false ? [0, []] : [$cursor, $result];
    }

    /**
     * Scans the given set for all values based on options.
	 * 扫描给定集合的所有值基于选项
     *
     * @param  string  $key
     * @param  mixed  $cursor
     * @param  array  $options
     * @return mixed
     */
    public function hscan($key, $cursor, $options = [])
    {
        $result = $this->client->hscan($key, $cursor,
            $options['match'] ?? '*',
            $options['count'] ?? 10
        );

        return $result === false ? [0, []] : [$cursor, $result];
    }

    /**
     * Scans the given set for all values based on options.
	 * 扫描给定集合的所有值基于选项
     *
     * @param  string  $key
     * @param  mixed  $cursor
     * @param  array  $options
     * @return mixed
     */
    public function sscan($key, $cursor, $options = [])
    {
        $result = $this->client->sscan($key, $cursor,
            $options['match'] ?? '*',
            $options['count'] ?? 10
        );

        return $result === false ? [0, []] : [$cursor, $result];
    }

    /**
     * Execute commands in a pipeline.
	 * 执行命令在管道中
     *
     * @param  callable|null  $callback
     * @return \Redis|array
     */
    public function pipeline(callable $callback = null)
    {
        $pipeline = $this->client()->pipeline();

        return is_null($callback)
            ? $pipeline
            : tap($pipeline, $callback)->exec();
    }

    /**
     * Execute commands in a transaction.
	 * 执行命令在事务中
     *
     * @param  callable|null  $callback
     * @return \Redis|array
     */
    public function transaction(callable $callback = null)
    {
        $transaction = $this->client()->multi();

        return is_null($callback)
            ? $transaction
            : tap($transaction, $callback)->exec();
    }

    /**
     * Evaluate a LUA script serverside, from the SHA1 hash of the script instead of the script itself.
	 * 从脚本的SHA1哈希值(而不是脚本本身)计算LUA脚本服务器端
     *
     * @param  string  $script
     * @param  int  $numkeys
     * @param  mixed  $arguments
     * @return mixed
     */
    public function evalsha($script, $numkeys, ...$arguments)
    {
        return $this->command('evalsha', [
            $this->script('load', $script), $arguments, $numkeys,
        ]);
    }

    /**
     * Evaluate a script and return its result.
	 * 对脚本求值并返回结果
     *
     * @param  string  $script
     * @param  int  $numberOfKeys
     * @param  dynamic  $arguments
     * @return mixed
     */
    public function eval($script, $numberOfKeys, ...$arguments)
    {
        return $this->command('eval', [$script, $arguments, $numberOfKeys]);
    }

    /**
     * Subscribe to a set of given channels for messages.
	 * 订阅一组给定的通道为消息
     *
     * @param  array|string  $channels
     * @param  \Closure  $callback
     * @return void
     */
    public function subscribe($channels, Closure $callback)
    {
        $this->client->subscribe((array) $channels, function ($redis, $channel, $message) use ($callback) {
            $callback($message, $channel);
        });
    }

    /**
     * Subscribe to a set of given channels with wildcards.
	 * 订阅一组给定的通道使用通配符
     *
     * @param  array|string  $channels
     * @param  \Closure  $callback
     * @return void
     */
    public function psubscribe($channels, Closure $callback)
    {
        $this->client->psubscribe((array) $channels, function ($redis, $pattern, $channel, $message) use ($callback) {
            $callback($message, $channel);
        });
    }

    /**
     * Subscribe to a set of given channels for messages.
	 * 订阅一组给定的通道为消息
     *
     * @param  array|string  $channels
     * @param  \Closure  $callback
     * @param  string  $method
     * @return void
     */
    public function createSubscription($channels, Closure $callback, $method = 'subscribe')
    {
        //
    }

    /**
     * Flush the selected Redis database.
	 * 刷新所选Redis数据库
     *
     * @return void
     */
    public function flushdb()
    {
        if (! $this->client instanceof RedisCluster) {
            return $this->command('flushdb');
        }

        foreach ($this->client->_masters() as $master) {
            $this->client->flushDb($master);
        }
    }

    /**
     * Execute a raw command.
	 * 执行原始命令
     *
     * @param  array  $parameters
     * @return mixed
     */
    public function executeRaw(array $parameters)
    {
        return $this->command('rawCommand', $parameters);
    }

    /**
     * Run a command against the Redis database.
	 * 运行命令对Redis数据库
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function command($method, array $parameters = [])
    {
        try {
            return parent::command($method, $parameters);
        } catch (RedisException $e) {
            if (Str::contains($e->getMessage(), 'went away')) {
                $this->client = $this->connector ? call_user_func($this->connector) : $this->client;
            }

            throw $e;
        }
    }

    /**
     * Disconnects from the Redis instance.
	 * 断开与Redis实例的连接
     *
     * @return void
     */
    public function disconnect()
    {
        $this->client->close();
    }

    /**
     * Apply prefix to the given key if necessary.
	 * 应用prefix对给定的键必要时
     *
     * @param  string  $key
     * @return string
     */
    private function applyPrefix($key)
    {
        $prefix = (string) $this->client->getOption(Redis::OPT_PREFIX);

        return $prefix.$key;
    }

    /**
     * Pass other method calls down to the underlying client.
	 * 调用其他方法传递给底层客户端
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return parent::__call(strtolower($method), $parameters);
    }
}
