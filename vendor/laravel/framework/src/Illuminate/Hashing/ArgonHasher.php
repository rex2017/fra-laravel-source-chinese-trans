<?php
/**
 * 哈希散列
 */

namespace Illuminate\Hashing;

use Illuminate\Contracts\Hashing\Hasher as HasherContract;
use RuntimeException;

class ArgonHasher extends AbstractHasher implements HasherContract
{
    /**
     * The default memory cost factor.
	 * 默认内存
     *
     * @var int
     */
    protected $memory = 1024;

    /**
     * The default time cost factor.
	 * 默认的时间成本因子
     *
     * @var int
     */
    protected $time = 2;

    /**
     * The default threads factor.
	 * 默认的线程因子
     *
     * @var int
     */
    protected $threads = 2;

    /**
     * Indicates whether to perform an algorithm check.
	 * 是否进行算法检查
     *
     * @var bool
     */
    protected $verifyAlgorithm = false;

    /**
     * Create a new hasher instance.
	 * 创建新的哈希实例
     *
     * @param  array  $options
     * @return void
     */
    public function __construct(array $options = [])
    {
        $this->time = $options['time'] ?? $this->time;
        $this->memory = $options['memory'] ?? $this->memory;
        $this->threads = $options['threads'] ?? $this->threads;
        $this->verifyAlgorithm = $options['verify'] ?? $this->verifyAlgorithm;
    }

    /**
     * Hash the given value.
	 * 哈希值
     *
     * @param  string  $value
     * @param  array  $options
     * @return string
     *
     * @throws \RuntimeException
     */
    public function make($value, array $options = [])
    {
        $hash = @password_hash($value, $this->algorithm(), [
            'memory_cost' => $this->memory($options),
            'time_cost' => $this->time($options),
            'threads' => $this->threads($options),
        ]);

        if (! is_string($hash)) {
            throw new RuntimeException('Argon2 hashing not supported.');
        }

        return $hash;
    }

    /**
     * Get the algorithm that should be used for hashing.
	 * 得到应该用于散列的算法
     *
     * @return int
     */
    protected function algorithm()
    {
        return PASSWORD_ARGON2I;
    }

    /**
     * Check the given plain value against a hash.
	 * 检查给定的普通值
     *
     * @param  string  $value
     * @param  string  $hashedValue
     * @param  array  $options
     * @return bool
     *
     * @throws \RuntimeException
     */
    public function check($value, $hashedValue, array $options = [])
    {
        if ($this->verifyAlgorithm && $this->info($hashedValue)['algoName'] !== 'argon2i') {
            throw new RuntimeException('This password does not use the Argon2i algorithm.');
        }

        return parent::check($value, $hashedValue, $options);
    }

    /**
     * Check if the given hash has been hashed using the given options.
	 * 检查给定的散列是否已经使用给定的选项进行了散列
     *
     * @param  string  $hashedValue
     * @param  array  $options
     * @return bool
     */
    public function needsRehash($hashedValue, array $options = [])
    {
        return password_needs_rehash($hashedValue, $this->algorithm(), [
            'memory_cost' => $this->memory($options),
            'time_cost' => $this->time($options),
            'threads' => $this->threads($options),
        ]);
    }

    /**
     * Set the default password memory factor.
	 * 设置默认密码内存
     *
     * @param  int  $memory
     * @return $this
     */
    public function setMemory(int $memory)
    {
        $this->memory = $memory;

        return $this;
    }

    /**
     * Set the default password timing factor.
	 * 设置默认密码定时因子
     *
     * @param  int  $time
     * @return $this
     */
    public function setTime(int $time)
    {
        $this->time = $time;

        return $this;
    }

    /**
     * Set the default password threads factor.
	 * 设置默认密码线程因子
     *
     * @param  int  $threads
     * @return $this
     */
    public function setThreads(int $threads)
    {
        $this->threads = $threads;

        return $this;
    }

    /**
     * Extract the memory cost value from the options array.
	 * 提取内存成本值从选项数组中
     *
     * @param  array  $options
     * @return int
     */
    protected function memory(array $options)
    {
        return $options['memory'] ?? $this->memory;
    }

    /**
     * Extract the time cost value from the options array.
	 * 提取内存成本值从选项数组中
     *
     * @param  array  $options
     * @return int
     */
    protected function time(array $options)
    {
        return $options['time'] ?? $this->time;
    }

    /**
     * Extract the threads value from the options array.
	 * 提取线程值从选项数组
     *
     * @param  array  $options
     * @return int
     */
    protected function threads(array $options)
    {
        return $options['threads'] ?? $this->threads;
    }
}
