<?php
/**
 * 契约，哈希接口
 */

namespace Illuminate\Contracts\Hashing;

interface Hasher
{
    /**
     * Get information about the given hashed value.
	 * 得到给定哈希值信息
     *
     * @param  string  $hashedValue
     * @return array
     */
    public function info($hashedValue);

    /**
     * Hash the given value.
	 * 哈希值
     *
     * @param  string  $value
     * @param  array  $options
     * @return string
     */
    public function make($value, array $options = []);

    /**
     * Check the given plain value against a hash.
	 * 检查哈希对比给定的明文值
     *
     * @param  string  $value
     * @param  string  $hashedValue
     * @param  array  $options
     * @return bool
     */
    public function check($value, $hashedValue, array $options = []);

    /**
     * Check if the given hash has been hashed using the given options.
	 * 检查给定的哈希是否已被哈希
     *
     * @param  string  $hashedValue
     * @param  array  $options
     * @return bool
     */
    public function needsRehash($hashedValue, array $options = []);
}
