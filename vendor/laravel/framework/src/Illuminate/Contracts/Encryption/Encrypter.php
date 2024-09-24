<?php
/**
 * 契约，加密接口
 */

namespace Illuminate\Contracts\Encryption;

interface Encrypter
{
    /**
     * Encrypt the given value.
	 * 加密给定的值
     *
     * @param  mixed  $value
     * @param  bool  $serialize
     * @return string
     *
     * @throws \Illuminate\Contracts\Encryption\EncryptException
     */
    public function encrypt($value, $serialize = true);

    /**
     * Decrypt the given value.
	 * 解密给定的值
     *
     * @param  string  $payload
     * @param  bool  $unserialize
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Encryption\DecryptException
     */
    public function decrypt($payload, $unserialize = true);
}
