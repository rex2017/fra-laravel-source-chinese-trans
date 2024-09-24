<?php
/**
 * 哈希管理
 */

namespace Illuminate\Hashing;

use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Manager;

class HashManager extends Manager implements Hasher
{
    /**
     * Create an instance of the Bcrypt hash Driver.
	 * 创建哈希驱动实例
     *
     * @return \Illuminate\Hashing\BcryptHasher
     */
    public function createBcryptDriver()
    {
        return new BcryptHasher($this->config->get('hashing.bcrypt') ?? []);
    }

    /**
     * Create an instance of the Argon2i hash Driver.
	 * 创建Argon2i哈希驱动程序实例
     *
     * @return \Illuminate\Hashing\ArgonHasher
     */
    public function createArgonDriver()
    {
        return new ArgonHasher($this->config->get('hashing.argon') ?? []);
    }

    /**
     * Create an instance of the Argon2id hash Driver.
	 * 创建Argon2i哈希驱动程序实例
     *
     * @return \Illuminate\Hashing\Argon2IdHasher
     */
    public function createArgon2idDriver()
    {
        return new Argon2IdHasher($this->config->get('hashing.argon') ?? []);
    }

    /**
     * Get information about the given hashed value.
	 * 得到有关给定散列值的信息
     *
     * @param  string  $hashedValue
     * @return array
     */
    public function info($hashedValue)
    {
        return $this->driver()->info($hashedValue);
    }

    /**
     * Hash the given value.
	 * 哈希给定值
     *
     * @param  string  $value
     * @param  array  $options
     * @return string
     */
    public function make($value, array $options = [])
    {
        return $this->driver()->make($value, $options);
    }

    /**
     * Check the given plain value against a hash.
	 * 检查给定的普通值根据散列
     *
     * @param  string  $value
     * @param  string  $hashedValue
     * @param  array  $options
     * @return bool
     */
    public function check($value, $hashedValue, array $options = [])
    {
        return $this->driver()->check($value, $hashedValue, $options);
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
        return $this->driver()->needsRehash($hashedValue, $options);
    }

    /**
     * Get the default driver name.
	 * 得到默认驱动名
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->config->get('hashing.driver', 'bcrypt');
    }
}
