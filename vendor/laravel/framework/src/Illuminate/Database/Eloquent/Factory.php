<?php
/**
 * 数据库，Eloquent工厂
 */

namespace Illuminate\Database\Eloquent;

use ArrayAccess;
use Faker\Generator as Faker;
use Symfony\Component\Finder\Finder;

class Factory implements ArrayAccess
{
    /**
     * The model definitions in the container.
	 * 容器中模型定义
     *
     * @var array
     */
    protected $definitions = [];

    /**
     * The registered model states.
	 * 已注册模型状态
     *
     * @var array
     */
    protected $states = [];

    /**
     * The registered after making callbacks.
	 * 回调后注册
     *
     * @var array
     */
    protected $afterMaking = [];

    /**
     * The registered after creating callbacks.
	 * 创建回调后注册
     *
     * @var array
     */
    protected $afterCreating = [];

    /**
     * The Faker instance for the builder.
	 * 生成器的Faker实例
     *
     * @var \Faker\Generator
     */
    protected $faker;

    /**
     * Create a new factory instance.
	 * 创建新的工厂实例
     *
     * @param  \Faker\Generator  $faker
     * @return void
     */
    public function __construct(Faker $faker)
    {
        $this->faker = $faker;
    }

    /**
     * Create a new factory container.
	 * 创建新的工厂容器
     *
     * @param  \Faker\Generator  $faker
     * @param  string|null  $pathToFactories
     * @return static
     */
    public static function construct(Faker $faker, $pathToFactories = null)
    {
        $pathToFactories = $pathToFactories ?: database_path('factories');

        return (new static($faker))->load($pathToFactories);
    }

    /**
     * Define a class with a given short-name.
	 * 定义一个类用给定的短名称
     *
     * @param  string  $class
     * @param  string  $name
     * @param  callable  $attributes
     * @return $this
     */
    public function defineAs($class, $name, callable $attributes)
    {
        return $this->define($class, $attributes, $name);
    }

    /**
     * Define a class with a given set of attributes.
	 * 定义一个类用一组给定的属性
     *
     * @param  string  $class
     * @param  callable  $attributes
     * @param  string  $name
     * @return $this
     */
    public function define($class, callable $attributes, $name = 'default')
    {
        $this->definitions[$class][$name] = $attributes;

        return $this;
    }

    /**
     * Define a state with a given set of attributes.
	 * 用一组给定的属性定义一个状态
     *
     * @param  string  $class
     * @param  string  $state
     * @param  callable|array  $attributes
     * @return $this
     */
    public function state($class, $state, $attributes)
    {
        $this->states[$class][$state] = $attributes;

        return $this;
    }

    /**
     * Define a callback to run after making a model.
	 * 定义一个在创建模型后运行的回调函数
     *
     * @param  string  $class
     * @param  callable  $callback
     * @param  string  $name
     * @return $this
     */
    public function afterMaking($class, callable $callback, $name = 'default')
    {
        $this->afterMaking[$class][$name][] = $callback;

        return $this;
    }

    /**
     * Define a callback to run after making a model with given state.
	 * 定义一个回调函数，在生成具有给定状态的模型后运行。
     *
     * @param  string  $class
     * @param  string  $state
     * @param  callable  $callback
     * @return $this
     */
    public function afterMakingState($class, $state, callable $callback)
    {
        return $this->afterMaking($class, $callback, $state);
    }

    /**
     * Define a callback to run after creating a model.
	 * 定义一个在创建模型后运行的回调函数
     *
     * @param  string  $class
     * @param  callable  $callback
     * @param  string  $name
     * @return $this
     */
    public function afterCreating($class, callable $callback, $name = 'default')
    {
        $this->afterCreating[$class][$name][] = $callback;

        return $this;
    }

    /**
     * Define a callback to run after creating a model with given state.
	 * 定义一个回调，以便在创建具有给定状态的模型后运行。
     *
     * @param  string  $class
     * @param  string  $state
     * @param  callable  $callback
     * @return $this
     */
    public function afterCreatingState($class, $state, callable $callback)
    {
        return $this->afterCreating($class, $callback, $state);
    }

    /**
     * Create an instance of the given model and persist it to the database.
	 * 创建给定模型的实例并将其持久化到数据库中
     *
     * @param  string  $class
     * @param  array  $attributes
     * @return mixed
     */
    public function create($class, array $attributes = [])
    {
        return $this->of($class)->create($attributes);
    }

    /**
     * Create an instance of the given model and type and persist it to the database.
	 * 创建给定模型和类型的实例，并将其持久化到数据库中。
     *
     * @param  string  $class
     * @param  string  $name
     * @param  array  $attributes
     * @return mixed
     */
    public function createAs($class, $name, array $attributes = [])
    {
        return $this->of($class, $name)->create($attributes);
    }

    /**
     * Create an instance of the given model.
	 * 创建给定模型的实例
     *
     * @param  string  $class
     * @param  array  $attributes
     * @return mixed
     */
    public function make($class, array $attributes = [])
    {
        return $this->of($class)->make($attributes);
    }

    /**
     * Create an instance of the given model and type.
	 * 创建给定模型和类型的实例
     *
     * @param  string  $class
     * @param  string  $name
     * @param  array  $attributes
     * @return mixed
     */
    public function makeAs($class, $name, array $attributes = [])
    {
        return $this->of($class, $name)->make($attributes);
    }

    /**
     * Get the raw attribute array for a given named model.
	 * 得到给定命名模型的原始属性数组
     *
     * @param  string  $class
     * @param  string  $name
     * @param  array  $attributes
     * @return array
     */
    public function rawOf($class, $name, array $attributes = [])
    {
        return $this->raw($class, $attributes, $name);
    }

    /**
     * Get the raw attribute array for a given model.
	 * 得到给定模型的原始属性数组
     *
     * @param  string  $class
     * @param  array  $attributes
     * @param  string  $name
     * @return array
     */
    public function raw($class, array $attributes = [], $name = 'default')
    {
        return array_merge(
            call_user_func($this->definitions[$class][$name], $this->faker), $attributes
        );
    }

    /**
     * Create a builder for the given model.
	 * 创建一个构建器为给定的模型
     *
     * @param  string  $class
     * @param  string  $name
     * @return \Illuminate\Database\Eloquent\FactoryBuilder
     */
    public function of($class, $name = 'default')
    {
        return new FactoryBuilder(
            $class, $name, $this->definitions, $this->states,
            $this->afterMaking, $this->afterCreating, $this->faker
        );
    }

    /**
     * Load factories from path.
	 * 加载工厂从路径
     *
     * @param  string  $path
     * @return $this
     */
    public function load($path)
    {
        $factory = $this;

        if (is_dir($path)) {
            foreach (Finder::create()->files()->name('*.php')->in($path) as $file) {
                require $file->getRealPath();
            }
        }

        return $factory;
    }

    /**
     * Determine if the given offset exists.
	 * 确定给定的偏移量是否存在
     *
     * @param  string  $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->definitions[$offset]);
    }

    /**
     * Get the value of the given offset.
	 * 得到给定偏移量的值
     *
     * @param  string  $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->make($offset);
    }

    /**
     * Set the given offset to the given value.
	 * 设置给定偏移量为给定值
     *
     * @param  string  $offset
     * @param  callable  $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->define($offset, $value);
    }

    /**
     * Unset the value at the given offset.
	 * 取消值的设置在给定的偏移量
     *
     * @param  string  $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->definitions[$offset]);
    }
}
