<?php
/**
 * 加密服务提供者
 */

namespace Illuminate\Encryption;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Opis\Closure\SerializableClosure;
use RuntimeException;

class EncryptionServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
	 * 注册服务提供者
     *
     * @return void
     */
    public function register()
    {
        $this->registerEncrypter();
        $this->registerOpisSecurityKey();
    }

    /**
     * Register the encrypter.
	 * 注册加密器
     *
     * @return void
     */
    protected function registerEncrypter()
    {
        $this->app->singleton('encrypter', function ($app) {
            $config = $app->make('config')->get('app');

            return new Encrypter($this->parseKey($config), $config['cipher']);
        });
    }

    /**
     * Configure Opis Closure signing for security.
	 * 配置Opis闭包签名为安全性
     *
     * @return void
     */
    protected function registerOpisSecurityKey()
    {
        $config = $this->app->make('config')->get('app');

        if (! class_exists(SerializableClosure::class) || empty($config['key'])) {
            return;
        }

        SerializableClosure::setSecretKey($this->parseKey($config));
    }

    /**
     * Parse the encryption key.
	 * 解析加密密钥
     *
     * @param  array  $config
     * @return string
     */
    protected function parseKey(array $config)
    {
        if (Str::startsWith($key = $this->key($config), $prefix = 'base64:')) {
            $key = base64_decode(Str::after($key, $prefix));
        }

        return $key;
    }

    /**
     * Extract the encryption key from the given configuration.
	 * 提取加密密钥从给定的配置中
     *
     * @param  array  $config
     * @return string
     *
     * @throws \RuntimeException
     */
    protected function key(array $config)
    {
        return tap($config['key'], function ($key) {
            if (empty($key)) {
                throw new RuntimeException(
                    'No application encryption key has been specified.'
                );
            }
        });
    }
}
