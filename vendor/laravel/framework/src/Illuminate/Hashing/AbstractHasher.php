<?php
/**
 * 哈希抽象类
 */

namespace Illuminate\Hashing;

abstract class AbstractHasher
{
    /**
     * Get information about the given hashed value.
	 * 得到信息关于给定哈希值
     *
     * @param  string  $hashedValue
     * @return array
     */
    public function info($hashedValue)
    {
        return password_get_info($hashedValue);
    }

    /**
     * Check the given plain value against a hash.
	 * 检查散列给定的普通值
     *
     * @param  string  $value
     * @param  string  $hashedValue
     * @param  array  $options
     * @return bool
     */
    public function check($value, $hashedValue, array $options = [])
    {
        if (strlen($hashedValue) === 0) {
            return false;
        }

        return password_verify($value, $hashedValue);
    }
}
