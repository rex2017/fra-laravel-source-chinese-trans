<?php
/**
 * 哈希散列
 */

namespace Illuminate\Hashing;

use RuntimeException;

class Argon2IdHasher extends ArgonHasher
{
    /**
     * Check the given plain value against a hash.
	 * 检查哈希散列值
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
        if ($this->verifyAlgorithm && $this->info($hashedValue)['algoName'] !== 'argon2id') {
            throw new RuntimeException('This password does not use the Argon2id algorithm.');
        }

        if (strlen($hashedValue) === 0) {
            return false;
        }

        return password_verify($value, $hashedValue);
    }

    /**
     * Get the algorithm that should be used for hashing.
	 * 得到用于散列的算法
     *
     * @return int
     */
    protected function algorithm()
    {
        return PASSWORD_ARGON2ID;
    }
}
