<?php
/**
 * 基础，别名加载
 */

namespace Illuminate\Foundation;

class AliasLoader
{
    /**
     * The array of class aliases.
	 * 别名数组
     *
     * @var array
     */
    protected $aliases;

    /**
     * Indicates if a loader has been registered.
	 * 是否加载已注册
     *
     * @var bool
     */
    protected $registered = false;

    /**
     * The namespace for all real-time facades.
	 * 门面命名空间
     *
     * @var string
     */
    protected static $facadeNamespace = 'Facades\\';

    /**
     * The singleton instance of the loader.
	 * 单例实例
     *
     * @var \Illuminate\Foundation\AliasLoader
     */
    protected static $instance;

    /**
     * Create a new AliasLoader instance.
	 * 创建新的别名导入实例
     *
     * @param  array  $aliases
     * @return void
     */
    private function __construct($aliases)
    {
        $this->aliases = $aliases;
    }

    /**
     * Get or create the singleton alias loader instance.
	 * 得到单例别名导入实例
     *
     * @param  array  $aliases
     * @return \Illuminate\Foundation\AliasLoader
     */
    public static function getInstance(array $aliases = [])
    {
        if (is_null(static::$instance)) {
            return static::$instance = new static($aliases);
        }

        $aliases = array_merge(static::$instance->getAliases(), $aliases);

        static::$instance->setAliases($aliases);

        return static::$instance;
    }

    /**
     * Load a class alias if it is registered.
	 * 导入类别名
     *
     * @param  string  $alias
     * @return bool|null
     */
    public function load($alias)
    {
        if (static::$facadeNamespace && strpos($alias, static::$facadeNamespace) === 0) {
            $this->loadFacade($alias);

            return true;
        }

        if (isset($this->aliases[$alias])) {
            return class_alias($this->aliases[$alias], $alias);
        }
    }

    /**
     * Load a real-time facade for the given alias.
	 * 导入门面
     *
     * @param  string  $alias
     * @return void
     */
    protected function loadFacade($alias)
    {
        require $this->ensureFacadeExists($alias);
    }

    /**
     * Ensure that the given alias has an existing real-time facade class.
	 * 确保给定的别名具有现有的实时facade类
     *
     * @param  string  $alias
     * @return string
     */
    protected function ensureFacadeExists($alias)
    {
        if (file_exists($path = storage_path('framework/cache/facade-'.sha1($alias).'.php'))) {
            return $path;
        }

        file_put_contents($path, $this->formatFacadeStub(
            $alias, file_get_contents(__DIR__.'/stubs/facade.stub')
        ));

        return $path;
    }

    /**
     * Format the facade stub with the proper namespace and class.
	 * 类格式化facade存根使用适当的名称空间
     *
     * @param  string  $alias
     * @param  string  $stub
     * @return string
     */
    protected function formatFacadeStub($alias, $stub)
    {
        $replacements = [
            str_replace('/', '\\', dirname(str_replace('\\', '/', $alias))),
            class_basename($alias),
            substr($alias, strlen(static::$facadeNamespace)),
        ];

        return str_replace(
            ['DummyNamespace', 'DummyClass', 'DummyTarget'], $replacements, $stub
        );
    }

    /**
     * Add an alias to the loader.
	 * 添加别名
     *
     * @param  string  $class
     * @param  string  $alias
     * @return void
     */
    public function alias($class, $alias)
    {
        $this->aliases[$class] = $alias;
    }

    /**
     * Register the loader on the auto-loader stack.
	 * 注册加载程序在自动加载程序堆栈上
     *
     * @return void
     */
    public function register()
    {
        if (! $this->registered) {
            $this->prependToLoaderStack();

            $this->registered = true;
        }
    }

    /**
     * Prepend the load method to the auto-loader stack.
	 * 准备加载方法至自动加载堆栈上
     *
     * @return void
     */
    protected function prependToLoaderStack()
    {
        spl_autoload_register([$this, 'load'], true, true);
    }

    /**
     * Get the registered aliases.
	 * 得到已注册别名
     *
     * @return array
     */
    public function getAliases()
    {
        return $this->aliases;
    }

    /**
     * Set the registered aliases.
	 * 设置注册别名
     *
     * @param  array  $aliases
     * @return void
     */
    public function setAliases(array $aliases)
    {
        $this->aliases = $aliases;
    }

    /**
     * Indicates if the loader has been registered.
	 * 指出是否加载已被注册
     *
     * @return bool
     */
    public function isRegistered()
    {
        return $this->registered;
    }

    /**
     * Set the "registered" state of the loader.
	 * 设置加载器的注册状态
	 * 
     *
     * @param  bool  $value
     * @return void
     */
    public function setRegistered($value)
    {
        $this->registered = $value;
    }

    /**
     * Set the real-time facade namespace.
	 * 设置门面命名空间
     *
     * @param  string  $namespace
     * @return void
     */
    public static function setFacadeNamespace($namespace)
    {
        static::$facadeNamespace = rtrim($namespace, '\\').'\\';
    }

    /**
     * Set the value of the singleton alias loader.
	 * 设置单例别名加载器的值
     *
     * @param  \Illuminate\Foundation\AliasLoader  $loader
     * @return void
     */
    public static function setInstance($loader)
    {
        static::$instance = $loader;
    }

    /**
     * Clone method.
	 * 克隆方法
     *
     * @return void
     */
    private function __clone()
    {
        //
    }
}
