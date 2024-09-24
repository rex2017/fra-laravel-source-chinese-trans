<?php
/**
 * 支持，门面加密
 */

namespace Illuminate\Support\Facades;

/**
 * @method static bool supported(string $key, string $cipher)
 * @method static string generateKey(string $cipher)
 * @method static string encrypt(mixed $value, bool $serialize = true)
 * @method static string encryptString(string $value)
 * @method static mixed decrypt(string $payload, bool $unserialize = true)
 * @method static string decryptString(string $payload)
 * @method static string getKey()
 *
 * @see \Illuminate\Encryption\Encrypter
 */
class Crypt extends Facade
{
    /**
     * Get the registered name of the component.
	 * 得到组件注册名
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'encrypter';
    }
}
