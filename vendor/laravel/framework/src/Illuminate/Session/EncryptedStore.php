<?php
/**
 * Session加密存储
 */

namespace Illuminate\Session;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;
use SessionHandlerInterface;

class EncryptedStore extends Store
{
    /**
     * The encrypter instance.
	 * 加密实例
     *
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    protected $encrypter;

    /**
     * Create a new session instance.
	 * 创建会话实例
     *
     * @param  string  $name
     * @param  \SessionHandlerInterface  $handler
     * @param  \Illuminate\Contracts\Encryption\Encrypter  $encrypter
     * @param  string|null  $id
     * @return void
     */
    public function __construct($name, SessionHandlerInterface $handler, EncrypterContract $encrypter, $id = null)
    {
        $this->encrypter = $encrypter;

        parent::__construct($name, $handler, $id);
    }

    /**
     * Prepare the raw string data from the session for unserialization.
	 * 准备来自会话的原始字符串数据以进行反序列化
     *
     * @param  string  $data
     * @return string
     */
    protected function prepareForUnserialize($data)
    {
        try {
            return $this->encrypter->decrypt($data);
        } catch (DecryptException $e) {
            return serialize([]);
        }
    }

    /**
     * Prepare the serialized session data for storage.
	 * 准备序列化的会话数据进行存储
     *
     * @param  string  $data
     * @return string
     */
    protected function prepareForStorage($data)
    {
        return $this->encrypter->encrypt($data);
    }

    /**
     * Get the encrypter instance.
	 * 得到加密实例
     *
     * @return \Illuminate\Contracts\Encryption\Encrypter
     */
    public function getEncrypter()
    {
        return $this->encrypter;
    }
}
