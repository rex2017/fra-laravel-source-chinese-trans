<?php
/**
 * 容器类，框架依赖的核心
 */

namespace Illuminate\Container;

use ArrayAccess;
use Closure;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container as ContainerContract;
use LogicException;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

class Container implements ArrayAccess, ContainerContract
{
    /**
     * The current globally available container (if any).
	 * 静态实例，全局
     *
     * @var static
     */
    protected static $instance;

    /**
     * An array of the types that have been resolved.
	 * 已解析的类型
     *
     * @var bool[]
     */
    protected $resolved = [];

    /**
     * The container's bindings.
	 * 容器绑定清单
     *
     * @var array[]
     */
    protected $bindings = [];

    /**
     * The container's method bindings.
	 * 容器方法绑定清单
     *
     * @var \Closure[]
     */
    protected $methodBindings = [];

    /**
     * The container's shared instances.
	 * 容器共享实例清单
     *
     * @var object[]
     */
    protected $instances = [];

    /**
     * The registered type aliases.
	 * 已注册类型别名清单
     *
     * @var string[]
     */
    protected $aliases = [];

    /**
     * The registered aliases keyed by the abstract name.
	 * 已注册抽象类别名
     *
     * @var array[]
     */
    protected $abstractAliases = [];

    /**
     * The extension closures for services.
	 * 服务扩展闭包清单
     *
     * @var array[]
     */
    protected $extenders = [];

    /**
     * All of the registered tags.
	 * 已注册标签清单
     *
     * @var array[]
     */
    protected $tags = [];

    /**
     * The stack of concretions currently being built.
	 * 当前正在创建的堆栈清单
     *
     * @var array[]
     */
    protected $buildStack = [];

    /**
     * The parameter override stack.
	 * 参数覆盖堆栈
     *
     * @var array[]
     */
    protected $with = [];

    /**
     * The contextual binding map.
	 * 上下文绑定映射
     *
     * @var array[]
     */
    public $contextual = [];

    /**
     * All of the registered rebound callbacks.
	 * 所有已注册的回调清单
     *
     * @var array[]
     */
    protected $reboundCallbacks = [];

    /**
     * All of the global resolving callbacks.
	 * 全局已解析回调
     *
     * @var \Closure[]
     */
    protected $globalResolvingCallbacks = [];

    /**
     * All of the global after resolving callbacks.
	 * 全局已解析之后回调
     *
     * @var \Closure[]
     */
    protected $globalAfterResolvingCallbacks = [];

    /**
     * All of the resolving callbacks by class type.
	 * 已解析回调
     *
     * @var array[]
     */
    protected $resolvingCallbacks = [];

    /**
     * All of the after resolving callbacks by class type.
	 * 解析之后回调
     *
     * @var array[]
     */
    protected $afterResolvingCallbacks = [];

    /**
     * Define a contextual binding.
	 * 定义上下文绑定
     *
     * @param  array|string  $concrete
     * @return \Illuminate\Contracts\Container\ContextualBindingBuilder
     */
    public function when($concrete)
    {
        $aliases = [];

        foreach (Util::arrayWrap($concrete) as $c) {
            $aliases[] = $this->getAlias($c);
        }

        return new ContextualBindingBuilder($this, $aliases);
    }

    /**
     * Determine if the given abstract type has been bound.
	 * 判断给定的抽象类型是否已绑定
     *
     * @param  string  $abstract
     * @return bool
     */
    public function bound($abstract)
    {
        return isset($this->bindings[$abstract]) ||
               isset($this->instances[$abstract]) ||
               $this->isAlias($abstract);
    }

    /**
     *  {@inheritdoc}
     */
    public function has($id)
    {
        return $this->bound($id);
    }

    /**
     * Determine if the given abstract type has been resolved.
	 * 确定是否给定的类已经被解析
     *
     * @param  string  $abstract
     * @return bool
     */
    public function resolved($abstract)
    {
        if ($this->isAlias($abstract)) {
            $abstract = $this->getAlias($abstract);
        }

        return isset($this->resolved[$abstract]) ||
               isset($this->instances[$abstract]);
    }

    /**
     * Determine if a given type is shared.
	 * 确定是否给定的类型共享
     *
     * @param  string  $abstract
     * @return bool
     */
    public function isShared($abstract)
    {
        return isset($this->instances[$abstract]) ||
               (isset($this->bindings[$abstract]['shared']) &&
               $this->bindings[$abstract]['shared'] === true);
    }

    /**
     * Determine if a given string is an alias.
	 * 确定是否给定的字符串别名
     *
     * @param  string  $name
     * @return bool
     */
    public function isAlias($name)
    {
        return isset($this->aliases[$name]);
    }

    /**
     * Register a binding with the container.
	 * 注册绑定至容器
     *
     * @param  string  $abstract
     * @param  \Closure|string|null  $concrete
     * @param  bool  $shared
     * @return void
     */
    public function bind($abstract, $concrete = null, $shared = false)
    {
        $this->dropStaleInstances($abstract);

        // If no concrete type was given, we will simply set the concrete type to the
        // abstract type. After that, the concrete type to be registered as shared
        // without being forced to state their classes in both of the parameters.
		// 如果没有给出具体类型，我们将简单地将具体类型设置为抽象类。
        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        // If the factory is not a Closure, it means it is just a class name which is
        // bound into this container to the abstract type and we will just wrap it
        // up inside its own Closure to give us more convenience when extending.
		// 如果工厂不是闭包，则意味着它只是一个类名绑定到容器中，我们将只包装它内部自
		// 有的闭合，在扩展时将给我们更多的便利。
        if (! $concrete instanceof Closure) {
            $concrete = $this->getClosure($abstract, $concrete);
        }

		// concrete=>$concrete,shared=>$shared
        $this->bindings[$abstract] = compact('concrete', 'shared');

        // If the abstract type was already resolved in this container we'll fire the
        // rebound listener so that any objects which have already gotten resolved
        // can have their copy of the object updated via the listener callbacks.
		// 如果抽象类已在此容器中解析，我们将启动监听器，以便任何已经解析的对象可以
		// 通过监听器回调来更新对象的副本。
        if ($this->resolved($abstract)) {
            $this->rebound($abstract);
        }
    }

    /**
     * Get the Closure to be used when building a type.
	 * 得到闭包以便被使用
     *
     * @param  string  $abstract
     * @param  string  $concrete
     * @return \Closure
     */
    protected function getClosure($abstract, $concrete)
    {
        return function ($container, $parameters = []) use ($abstract, $concrete) {
            if ($abstract == $concrete) {
                return $container->build($concrete);
            }

            return $container->resolve(
                $concrete, $parameters, $raiseEvents = false
            );
        };
    }

    /**
     * Determine if the container has a method binding.
	 * 确定是否容器已绑定某个方法
     *
     * @param  string  $method
     * @return bool
     */
    public function hasMethodBinding($method)
    {
        return isset($this->methodBindings[$method]);
    }

    /**
     * Bind a callback to resolve with Container::call.
	 * 绑定方法
     *
     * @param  array|string  $method
     * @param  \Closure  $callback
     * @return void
     */
    public function bindMethod($method, $callback)
    {
        $this->methodBindings[$this->parseBindMethod($method)] = $callback;
    }

    /**
     * Get the method to be bound in class@method format.
	 * 得到要绑定的方法
     *
     * @param  array|string  $method
     * @return string
     */
    protected function parseBindMethod($method)
    {
        if (is_array($method)) {
            return $method[0].'@'.$method[1];
        }

        return $method;
    }

    /**
     * Get the method binding for the given method.
	 * 得到绑定方法
     *
     * @param  string  $method
     * @param  mixed  $instance
     * @return mixed
     */
    public function callMethodBinding($method, $instance)
    {
        return call_user_func($this->methodBindings[$method], $instance, $this);
    }

    /**
     * Add a contextual binding to the container.
	 * 添加上下文绑定
     *
     * @param  string  $concrete
     * @param  string  $abstract
     * @param  \Closure|string  $implementation
     * @return void
     */
    public function addContextualBinding($concrete, $abstract, $implementation)
    {
        $this->contextual[$concrete][$this->getAlias($abstract)] = $implementation;
    }

    /**
     * Register a binding if it hasn't already been registered.
	 * 注册一个绑定，如果绑定尚未注册
     *
     * @param  string  $abstract
     * @param  \Closure|string|null  $concrete
     * @param  bool  $shared
     * @return void
     */
    public function bindIf($abstract, $concrete = null, $shared = false)
    {
        if (! $this->bound($abstract)) {
            $this->bind($abstract, $concrete, $shared);
        }
    }

    /**
     * Register a shared binding in the container.
	 * 注册共享绑定，单例
     *
     * @param  string  $abstract
     * @param  \Closure|string|null  $concrete
     * @return void
     */
    public function singleton($abstract, $concrete = null)
    {
		// public function bind 在上面245行
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Register a shared binding if it hasn't already been registered.
	 * 注册一个共享绑定
     *
     * @param  string  $abstract
     * @param  \Closure|string|null  $concrete
     * @return void
     */
    public function singletonIf($abstract, $concrete = null)
    {
        if (! $this->bound($abstract)) {
            $this->singleton($abstract, $concrete);
        }
    }

    /**
     * "Extend" an abstract type in the container.
	 * 扩展类
     *
     * @param  string  $abstract
     * @param  \Closure  $closure
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function extend($abstract, Closure $closure)
    {
        $abstract = $this->getAlias($abstract);

        if (isset($this->instances[$abstract])) {
            $this->instances[$abstract] = $closure($this->instances[$abstract], $this);

            $this->rebound($abstract);
        } else {
            $this->extenders[$abstract][] = $closure;

            if ($this->resolved($abstract)) {
                $this->rebound($abstract);
            }
        }
    }

    /**
     * Register an existing instance as shared in the container.
	 * 注册现存的实例做为容器的共享实例
     *
     * @param  string  $abstract
     * @param  mixed  $instance
     * @return mixed
     */
    public function instance($abstract, $instance)
    {
        $this->removeAbstractAlias($abstract);

        $isBound = $this->bound($abstract);

        unset($this->aliases[$abstract]);

        // We'll check to determine if this type has been bound before, and if it has
        // we will fire the rebound callbacks registered with the container and it
        // can be updated with consuming classes that have gotten resolved here.
		// 我们将检查以确定此类型之前是否已绑定，
		// 如果已绑定我们将触发容器中注册的回调。
        $this->instances[$abstract] = $instance;

        if ($isBound) {
            $this->rebound($abstract);
        }

        return $instance;
    }

    /**
     * Remove an alias from the contextual binding alias cache.
	 * 删除别名从上下文绑定别名缓存中
     *
     * @param  string  $searched
     * @return void
     */
    protected function removeAbstractAlias($searched)
    {
        if (! isset($this->aliases[$searched])) {
            return;
        }

        foreach ($this->abstractAliases as $abstract => $aliases) {
            foreach ($aliases as $index => $alias) {
                if ($alias == $searched) {
                    unset($this->abstractAliases[$abstract][$index]);
                }
            }
        }
    }

    /**
     * Assign a set of tags to a given binding.
	 * 分配一组标记为给定的绑定
     *
     * @param  array|string  $abstracts
     * @param  array|mixed  ...$tags
     * @return void
     */
    public function tag($abstracts, $tags)
    {
        $tags = is_array($tags) ? $tags : array_slice(func_get_args(), 1);

        foreach ($tags as $tag) {
            if (! isset($this->tags[$tag])) {
                $this->tags[$tag] = [];
            }

            foreach ((array) $abstracts as $abstract) {
                $this->tags[$tag][] = $abstract;
            }
        }
    }

    /**
     * Resolve all of the bindings for a given tag.
	 * 解析给定标记的所有绑定
     *
     * @param  string  $tag
     * @return iterable
     */
    public function tagged($tag)
    {
        if (! isset($this->tags[$tag])) {
            return [];
        }

        return new RewindableGenerator(function () use ($tag) {
            foreach ($this->tags[$tag] as $abstract) {
                yield $this->make($abstract);
            }
        }, count($this->tags[$tag]));
    }

    /**
     * Alias a type to a different name.
	 * 将类型别名为不同的名称
     *
     * @param  string  $abstract
     * @param  string  $alias
     * @return void
     *
     * @throws \LogicException
     */
    public function alias($abstract, $alias)
    {
        if ($alias === $abstract) {
            throw new LogicException("[{$abstract}] is aliased to itself.");
        }

        $this->aliases[$alias] = $abstract;

        $this->abstractAliases[$abstract][] = $alias;
    }

    /**
     * Bind a new callback to an abstract's rebind event.
	 * 将一个新的回调函数绑定到抽象的rebind事件
     *
     * @param  string  $abstract
     * @param  \Closure  $callback
     * @return mixed
     */
    public function rebinding($abstract, Closure $callback)
    {
        $this->reboundCallbacks[$abstract = $this->getAlias($abstract)][] = $callback;

        if ($this->bound($abstract)) {
            return $this->make($abstract);
        }
    }

    /**
     * Refresh an instance on the given target and method.
	 * 刷新目标方法的实例
     *
     * @param  string  $abstract
     * @param  mixed  $target
     * @param  string  $method
     * @return mixed
     */
    public function refresh($abstract, $target, $method)
    {
        return $this->rebinding($abstract, function ($app, $instance) use ($target, $method) {
            $target->{$method}($instance);
        });
    }

    /**
     * Fire the "rebound" callbacks for the given abstract type.
	 * 触发"反弹"回调为给定的抽象类型
     *
     * @param  string  $abstract
     * @return void
     */
    protected function rebound($abstract)
    {
        $instance = $this->make($abstract);

        foreach ($this->getReboundCallbacks($abstract) as $callback) {
            call_user_func($callback, $this, $instance);
        }
    }

    /**
     * Get the rebound callbacks for a given type.
	 * 得到给定类型的回调函数
     *
     * @param  string  $abstract
     * @return array
     */
    protected function getReboundCallbacks($abstract)
    {
        return $this->reboundCallbacks[$abstract] ?? [];
    }

    /**
     * Wrap the given closure such that its dependencies will be injected when executed.
	 * 包装给定的闭包，以便在执行时注入其依赖项
     *
     * @param  \Closure  $callback
     * @param  array  $parameters
     * @return \Closure
     */
    public function wrap(Closure $callback, array $parameters = [])
    {
        return function () use ($callback, $parameters) {
            return $this->call($callback, $parameters);
        };
    }

    /**
     * Call the given Closure / class@method and inject its dependencies.
	 * 调用给定的Closure/class@method并注入它的依赖项
     *
     * @param  callable|string  $callback
     * @param  array  $parameters
     * @param  string|null  $defaultMethod
     * @return mixed
     */
    public function call($callback, array $parameters = [], $defaultMethod = null)
    {
        return BoundMethod::call($this, $callback, $parameters, $defaultMethod);
    }

    /**
     * Get a closure to resolve the given type from the container.
	 * 得到一个闭包去解析给定的类
     *
     * @param  string  $abstract
     * @return \Closure
     */
    public function factory($abstract)
    {
        return function () use ($abstract) {
            return $this->make($abstract);
        };
    }

    /**
     * An alias function name for make().
	 * make()的别名函数名
     *
     * @param  string  $abstract
     * @param  array  $parameters
     * @return mixed
     */
    public function makeWith($abstract, array $parameters = [])
    {
        return $this->make($abstract, $parameters);
    }

    /**
     * Resolve the given type from the container.
	 * 从容器里解析出实例
     *
     * @param  string  $abstract
     * @param  array  $parameters
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function make($abstract, array $parameters = [])
    {
        return $this->resolve($abstract, $parameters);
    }

    /**
     *  {@inheritdoc}
     */
    public function get($id)
    {
        try {
            return $this->resolve($id);
        } catch (Exception $e) {
            if ($this->has($id)) {
                throw $e;
            }

            throw new EntryNotFoundException($id, $e->getCode(), $e);
        }
    }

    /**
     * Resolve the given type from the container.
	 * 从容器里解析出实例，实际执行的方法
     *
     * @param  string  $abstract
     * @param  array  $parameters
     * @param  bool  $raiseEvents
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function resolve($abstract, $parameters = [], $raiseEvents = true)
    {
        $abstract = $this->getAlias($abstract);
		
        $needsContextualBuild = ! empty($parameters) || ! is_null(
            $this->getContextualConcrete($abstract)
        );

        // If an instance of the type is currently being managed as a singleton we'll
        // just return an existing instance instead of instantiating new instances
        // so the developer can keep using the same objects instance every time.
		// 如果改类型的实例当前作为单例进行管理，我们将只返回一个现有实例。
		// 这样开发人员每次都可以继续使用相同的对象实例。
        if (isset($this->instances[$abstract]) && ! $needsContextualBuild) {
            return $this->instances[$abstract];
        }

        $this->with[] = $parameters;

		// 解析出具体的类
        $concrete = $this->getConcrete($abstract);

        // We're ready to instantiate an instance of the concrete type registered for
        // the binding. This will instantiate the types, as well as resolve any of
        // its "nested" dependencies recursively until all have gotten resolved.
		// 我们已经准备好实例化为注册的具体类型的实例绑定。
		// 这将实例化类型，并解析以下任何类型它的"嵌套"依赖关系是递归的，
		// 直到所有依赖关系都得到解决。
        if ($this->isBuildable($concrete, $abstract)) {
            $object = $this->build($concrete);
        } else {
            $object = $this->make($concrete);
        }

        // If we defined any extenders for this type, we'll need to spin through them
        // and apply them to the object being built. This allows for the extension
        // of services, such as changing configuration or decorating the object.
		// 如果我们为这种类型定义了任何扩展器，我们需要旋转它们并将它们应用于正在构建的对象。
		// 这允许扩展例如更改配置或装饰对象。
        foreach ($this->getExtenders($abstract) as $extender) {
            $object = $extender($object, $this);
        }

        // If the requested type is registered as a singleton we'll want to cache off
        // the instances in "memory" so we can return it later without creating an
        // entirely new instance of an object on each subsequent request for it.
		// 如果请求类型被注册为单例，我们将需要缓存实例保存在"内存"中，
		// 以便我们稍后可以返回它，而无需创建新的实例。
        if ($this->isShared($abstract) && ! $needsContextualBuild) {
            $this->instances[$abstract] = $object;
        }

        if ($raiseEvents) {
            $this->fireResolvingCallbacks($abstract, $object);
        }

        // Before returning, we will also set the resolved flag to "true" and pop off
        // the parameter overrides for this build. After those two things are done
        // we will be ready to return back the fully constructed class instance.
		// 在返回之前，我们还将把已解决的标志设置为"true"并弹出参数重写。
		// 在这两件事做完后我们还将返回完全构造的类实例。
        $this->resolved[$abstract] = true;

        array_pop($this->with);

        return $object;
    }

    /**
     * Get the concrete type for a given abstract.
	 * 得到实际类型
     *
     * @param  string  $abstract
     * @return mixed
     */
    protected function getConcrete($abstract)
    {
        if (! is_null($concrete = $this->getContextualConcrete($abstract))) {
            return $concrete;
        }

        // If we don't have a registered resolver or concrete for the type, we'll just
        // assume each type is a concrete name and will attempt to resolve it as is
        // since the container should be able to resolve concretes automatically.
		// 如果我们没有该类型的注册解析器或实体，我们只需要假设每种类型都是一个具体的名称，
		// 并将尝试按原样解析它，因为容器能自动解析实体。
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        return $abstract;
    }

    /**
     * Get the contextual concrete binding for the given abstract.
	 * 得到给定抽象的上下文具体绑定
     *
     * @param  string  $abstract
     * @return \Closure|string|null
     */
    protected function getContextualConcrete($abstract)
    {
        if (! is_null($binding = $this->findInContextualBindings($abstract))) {
            return $binding;
        }

        // Next we need to see if a contextual binding might be bound under an alias of the
        // given abstract type. So, we will need to check if any aliases exist with this
        // type and then spin through them and check for contextual bindings on these.
		// 接下来，我们需要看看上下文绑定是否可以绑定在别名下给定抽象类型。
		// 因此，我们需要检查是否存在与此相关的别名键入，
		// 然后旋转它们，检查它们的上下文绑定。
        if (empty($this->abstractAliases[$abstract])) {
            return;
        }

        foreach ($this->abstractAliases[$abstract] as $alias) {
            if (! is_null($binding = $this->findInContextualBindings($alias))) {
                return $binding;
            }
        }
    }

    /**
     * Find the concrete binding for the given abstract in the contextual binding array.
	 * 在上下文绑定数组中查找给定抽象的具体绑定
     *
     * @param  string  $abstract
     * @return \Closure|string|null
     */
    protected function findInContextualBindings($abstract)
    {
        return $this->contextual[end($this->buildStack)][$abstract] ?? null;
    }

    /**
     * Determine if the given concrete is buildable.
	 * 确定给定的实体是否可建造
     *
     * @param  mixed  $concrete
     * @param  string  $abstract
     * @return bool
     */
    protected function isBuildable($concrete, $abstract)
    {
        return $concrete === $abstract || $concrete instanceof Closure;
    }

    /**
     * Instantiate a concrete instance of the given type.
	 * 实例化给定类型的具体实例
     *
     * @param  string  $concrete
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function build($concrete)
    {
        // If the concrete type is actually a Closure, we will just execute it and
        // hand back the results of the functions, which allows functions to be
        // used as resolvers for more fine-tuned resolution of these objects.
		// 如果具体类型实例上是闭包，我们只需执行它返回函数的结果，
		// 这允许函数用作解析器，以更精细地调整这些对象的分辨率。
        if ($concrete instanceof Closure) {
            return $concrete($this, $this->getLastParameterOverride());
        }

        try {
            $reflector = new ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            throw new BindingResolutionException("Target class [$concrete] does not exist.", 0, $e);
        }

        // If the type is not instantiable, the developer is attempting to resolve
        // an abstract type such as an Interface or Abstract Class and there is
        // no binding registered for the abstractions so we need to bail out.
		// 如果类型不可实例化，开发人员将尝试解析一个抽象类型，如接口或抽象类，
		// 并且有抽象没有注册绑定，所以我们需要退出。
        if (! $reflector->isInstantiable()) {
            return $this->notInstantiable($concrete);
        }

        $this->buildStack[] = $concrete;

        $constructor = $reflector->getConstructor();

        // If there are no constructors, that means there are no dependencies then
        // we can just resolve the instances of the objects right away, without
        // resolving any other types or dependencies out of these containers.
		// 如果没有构造函数，则意味着没有依赖关系。
		// 我们可以立即解析对象的实例，而无需从这些容器中解析任何其他类型或依赖关系。
        if (is_null($constructor)) {
            array_pop($this->buildStack);

            return new $concrete;
        }

        $dependencies = $constructor->getParameters();

        // Once we have all the constructor's parameters we can create each of the
        // dependency instances and then use the reflection instances to make a
        // new instance of this class, injecting the created dependencies in.
		// 一旦我们有了所有构造函数的参数，我们就可以创建每个依赖实例。
		// 然后使用反射实例进行创建这个类的新实例，将创建的依赖项注入。
        try {
            $instances = $this->resolveDependencies($dependencies);
        } catch (BindingResolutionException $e) {
            array_pop($this->buildStack);

            throw $e;
        }

        array_pop($this->buildStack);

        return $reflector->newInstanceArgs($instances);
    }

    /**
     * Resolve all of the dependencies from the ReflectionParameters.
	 * 解析来自反射参数的所有依赖项
     *
     * @param  \ReflectionParameter[]  $dependencies
     * @return array
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function resolveDependencies(array $dependencies)
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            // If this dependency has a override for this particular build we will use
            // that instead as the value. Otherwise, we will continue with this run
            // of resolutions and let reflection attempt to determine the result.
			// 如果这个依赖项对此特定版本有覆盖，我们将使用代替作为价值。
			// 否则，我们将继续运行方案并让反射来决定结果。
            if ($this->hasParameterOverride($dependency)) {
                $results[] = $this->getParameterOverride($dependency);

                continue;
            }

            // If the class is null, it means the dependency is a string or some other
            // primitive type which we can not resolve since it is not a class and
            // we will just bomb out with an error since we have no-where to go.
			// 如果类为null,则表示依赖关系是字符串或其他类型。
			// 我们无法解析原型类型，因为它不是一个类。
			// 因为我们没有地方可去，所以我们只会出现一个错误。
            $results[] = is_null(Util::getParameterClassName($dependency))
                            ? $this->resolvePrimitive($dependency)
                            : $this->resolveClass($dependency);
        }

        return $results;
    }

    /**
     * Determine if the given dependency has a parameter override.
	 * 确定给定的依赖项是否具有参数覆盖
     *
     * @param  \ReflectionParameter  $dependency
     * @return bool
     */
    protected function hasParameterOverride($dependency)
    {
        return array_key_exists(
            $dependency->name, $this->getLastParameterOverride()
        );
    }

    /**
     * Get a parameter override for a dependency.
	 * 得到依赖项的参数覆盖
     *
     * @param  \ReflectionParameter  $dependency
     * @return mixed
     */
    protected function getParameterOverride($dependency)
    {
        return $this->getLastParameterOverride()[$dependency->name];
    }

    /**
     * Get the last parameter override.
	 * 得到最后一个参数覆盖
     *
     * @return array
     */
    protected function getLastParameterOverride()
    {
        return count($this->with) ? end($this->with) : [];
    }

    /**
     * Resolve a non-class hinted primitive dependency.
	 * 解析非类暗示的原语依赖
     *
     * @param  \ReflectionParameter  $parameter
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function resolvePrimitive(ReflectionParameter $parameter)
    {
        if (! is_null($concrete = $this->getContextualConcrete('$'.$parameter->getName()))) {
            return $concrete instanceof Closure ? $concrete($this) : $concrete;
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        $this->unresolvablePrimitive($parameter);
    }

    /**
     * Resolve a class based dependency from the container.
	 * 解析基于类的依赖项从容器中
     *
     * @param  \ReflectionParameter  $parameter
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function resolveClass(ReflectionParameter $parameter)
    {
        try {
            return $this->make(Util::getParameterClassName($parameter));
        }

        // If we can not resolve the class instance, we will check to see if the value
        // is optional, and if it is we will return the optional parameter value as
        // the value of the dependency, similarly to how we do this with scalars.
		// 如果我们无法解析类实例，我们将检查值是否是可选的。
		// 如果是，我们将返回可选参数值为依赖关系的值，类型于我们如何使用标量。
        catch (BindingResolutionException $e) {
            if ($parameter->isOptional()) {
                return $parameter->getDefaultValue();
            }

            throw $e;
        }
    }

    /**
     * Throw an exception that the concrete is not instantiable.
	 * 抛出一个异常，表明该具体对象不可实例化
     *
     * @param  string  $concrete
     * @return void
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function notInstantiable($concrete)
    {
        if (! empty($this->buildStack)) {
            $previous = implode(', ', $this->buildStack);

            $message = "Target [$concrete] is not instantiable while building [$previous].";
        } else {
            $message = "Target [$concrete] is not instantiable.";
        }

        throw new BindingResolutionException($message);
    }

    /**
     * Throw an exception for an unresolvable primitive.
	 * 为不可解析的原语抛出异常
     *
     * @param  \ReflectionParameter  $parameter
     * @return void
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function unresolvablePrimitive(ReflectionParameter $parameter)
    {
        $message = "Unresolvable dependency resolving [$parameter] in class {$parameter->getDeclaringClass()->getName()}";

        throw new BindingResolutionException($message);
    }

    /**
     * Register a new resolving callback.
	 * 注册一个新的解析回调
     *
     * @param  \Closure|string  $abstract
     * @param  \Closure|null  $callback
     * @return void
     */
    public function resolving($abstract, Closure $callback = null)
    {
        if (is_string($abstract)) {
            $abstract = $this->getAlias($abstract);
        }

        if (is_null($callback) && $abstract instanceof Closure) {
            $this->globalResolvingCallbacks[] = $abstract;
        } else {
            $this->resolvingCallbacks[$abstract][] = $callback;
        }
    }

    /**
     * Register a new after resolving callback for all types.
	 * 为所有类型解析回调后注册一个new
     *
     * @param  \Closure|string  $abstract
     * @param  \Closure|null  $callback
     * @return void
     */
    public function afterResolving($abstract, Closure $callback = null)
    {
        if (is_string($abstract)) {
            $abstract = $this->getAlias($abstract);
        }

        if ($abstract instanceof Closure && is_null($callback)) {
            $this->globalAfterResolvingCallbacks[] = $abstract;
        } else {
            $this->afterResolvingCallbacks[$abstract][] = $callback;
        }
    }

    /**
     * Fire all of the resolving callbacks.
	 * 触发所有解析回调
     *
     * @param  string  $abstract
     * @param  mixed  $object
     * @return void
     */
    protected function fireResolvingCallbacks($abstract, $object)
    {
        $this->fireCallbackArray($object, $this->globalResolvingCallbacks);

        $this->fireCallbackArray(
            $object, $this->getCallbacksForType($abstract, $object, $this->resolvingCallbacks)
        );

        $this->fireAfterResolvingCallbacks($abstract, $object);
    }

    /**
     * Fire all of the after resolving callbacks.
	 * 触发所有的在解析回调后
     *
     * @param  string  $abstract
     * @param  mixed  $object
     * @return void
     */
    protected function fireAfterResolvingCallbacks($abstract, $object)
    {
        $this->fireCallbackArray($object, $this->globalAfterResolvingCallbacks);

        $this->fireCallbackArray(
            $object, $this->getCallbacksForType($abstract, $object, $this->afterResolvingCallbacks)
        );
    }

    /**
     * Get all callbacks for a given type.
	 * 得到给定类型的所有回调
     *
     * @param  string  $abstract
     * @param  object  $object
     * @param  array  $callbacksPerType
     * @return array
     */
    protected function getCallbacksForType($abstract, $object, array $callbacksPerType)
    {
        $results = [];

        foreach ($callbacksPerType as $type => $callbacks) {
            if ($type === $abstract || $object instanceof $type) {
                $results = array_merge($results, $callbacks);
            }
        }

        return $results;
    }

    /**
     * Fire an array of callbacks with an object.
	 * 触发回调数组使用对象
     *
     * @param  mixed  $object
     * @param  array  $callbacks
     * @return void
     */
    protected function fireCallbackArray($object, array $callbacks)
    {
        foreach ($callbacks as $callback) {
            $callback($object, $this);
        }
    }

    /**
     * Get the container's bindings.
	 * 得到容器绑定清单
     *
     * @return array
     */
    public function getBindings()
    {
        return $this->bindings;
    }

    /**
     * Get the alias for an abstract if available.
	 * 得到摘要的别名如果可用
     *
     * @param  string  $abstract
     * @return string
     */
    public function getAlias($abstract)
    {
        if (! isset($this->aliases[$abstract])) {
            return $abstract;
        }

        return $this->getAlias($this->aliases[$abstract]);
    }

    /**
     * Get the extender callbacks for a given type.
	 * 得到给定类型的扩展程序回调
     *
     * @param  string  $abstract
     * @return array
     */
    protected function getExtenders($abstract)
    {
        $abstract = $this->getAlias($abstract);

        return $this->extenders[$abstract] ?? [];
    }

    /**
     * Remove all of the extender callbacks for a given type.
	 * 删除给定类型的所有扩展程序回调
     *
     * @param  string  $abstract
     * @return void
     */
    public function forgetExtenders($abstract)
    {
        unset($this->extenders[$this->getAlias($abstract)]);
    }

    /**
     * Drop all of the stale instances and aliases.
	 * 删除实例和别名
     *
     * @param  string  $abstract
     * @return void
     */
    protected function dropStaleInstances($abstract)
    {
        unset($this->instances[$abstract], $this->aliases[$abstract]);
    }

    /**
     * Remove a resolved instance from the instance cache.
	 * 移除一个已解析的实例从实例缓存中
     *
     * @param  string  $abstract
     * @return void
     */
    public function forgetInstance($abstract)
    {
        unset($this->instances[$abstract]);
    }

    /**
     * Clear all of the instances from the container.
	 * 清除所有实例从容器中
     *
     * @return void
     */
    public function forgetInstances()
    {
        $this->instances = [];
    }

    /**
     * Flush the container of all bindings and resolved instances.
	 * 刷新所有绑定和解析实例的容器
     *
     * @return void
     */
    public function flush()
    {
        $this->aliases = [];
        $this->resolved = [];
        $this->bindings = [];
        $this->instances = [];
        $this->abstractAliases = [];
    }

    /**
     * Get the globally available instance of the container.
	 * 得到容器的全局可用实例
     *
     * @return static
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    /**
     * Set the shared instance of the container.
	 * 设置容器的共享实例
     *
     * @param  \Illuminate\Contracts\Container\Container|null  $container
     * @return \Illuminate\Contracts\Container\Container|static
     */
    public static function setInstance(ContainerContract $container = null)
    {
        return static::$instance = $container;
    }

    /**
     * Determine if a given offset exists.
	 * 确定给定的偏移量是否存在
     *
     * @param  string  $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->bound($key);
    }

    /**
     * Get the value at a given offset.
	 * 得到给定偏移量处的值
     *
     * @param  string  $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->make($key);
    }

    /**
     * Set the value at a given offset.
	 * 设置值在给定的偏移量处
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        $this->bind($key, $value instanceof Closure ? $value : function () use ($value) {
            return $value;
        });
    }

    /**
     * Unset the value at a given offset.
	 * 取消值的设置在给定偏移量处
     *
     * @param  string  $key
     * @return void
     */
    public function offsetUnset($key)
    {
        unset($this->bindings[$key], $this->instances[$key], $this->resolved[$key]);
    }

    /**
     * Dynamically access container services.
	 * 动态获取容器服务
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this[$key];
    }

    /**
     * Dynamically set container services.
	 * 动态设置容器服务
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this[$key] = $value;
    }
}
