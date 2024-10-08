<?php
/**
 * 支持，环境配置
 */

namespace Illuminate\Support;

use Dotenv\Environment\Adapter\EnvConstAdapter;
use Dotenv\Environment\Adapter\PutenvAdapter;
use Dotenv\Environment\Adapter\ServerConstAdapter;
use Dotenv\Environment\DotenvFactory;
use PhpOption\Option;

class Env
{
    /**
     * Indicates if the putenv adapter is enabled.
	 * 确定是否写环境是否启用
     *
     * @var bool
     */
    protected static $putenv = true;

    /**
     * The environment factory instance.
	 * 环境工厂实例
     *
     * @var \Dotenv\Environment\FactoryInterface|null
     */
    protected static $factory;

    /**
     * The environment variables instance.
	 * 环境变更实例
     *
     * @var \Dotenv\Environment\VariablesInterface|null
     */
    protected static $variables;

    /**
     * Enable the putenv adapter.
	 * 启用putenv适配器
     *
     * @return void
     */
    public static function enablePutenv()
    {
        static::$putenv = true;
        static::$factory = null;
        static::$variables = null;
    }

    /**
     * Disable the putenv adapter.
	 * 禁用putenv适配器
     *
     * @return void
     */
    public static function disablePutenv()
    {
        static::$putenv = false;
        static::$factory = null;
        static::$variables = null;
    }

    /**
     * Get the environment factory instance.
	 * 得到环境工厂实例
     *
     * @return \Dotenv\Environment\FactoryInterface
     */
    public static function getFactory()
    {
        if (static::$factory === null) {
            $adapters = array_merge(
                [new EnvConstAdapter, new ServerConstAdapter],
                static::$putenv ? [new PutenvAdapter] : []
            );

            static::$factory = new DotenvFactory($adapters);
        }

        return static::$factory;
    }

    /**
     * Get the environment variables instance.
	 * 得到环境变量实例
     *
     * @return \Dotenv\Environment\VariablesInterface
     */
    public static function getVariables()
    {
        if (static::$variables === null) {
            static::$variables = static::getFactory()->createImmutable();
        }

        return static::$variables;
    }

    /**
     * Gets the value of an environment variable.
	 * 得到环境变量值
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        return Option::fromValue(static::getVariables()->get($key))
            ->map(function ($value) {
                switch (strtolower($value)) {
                    case 'true':
                    case '(true)':
                        return true;
                    case 'false':
                    case '(false)':
                        return false;
                    case 'empty':
                    case '(empty)':
                        return '';
                    case 'null':
                    case '(null)':
                        return;
                }

                if (preg_match('/\A([\'"])(.*)\1\z/', $value, $matches)) {
                    return $matches[2];
                }

                return $value;
            })
            ->getOrCall(function () use ($default) {
                return value($default);
            });
    }
}
