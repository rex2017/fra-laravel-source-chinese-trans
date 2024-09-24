<?php
/**
 * 视图工厂
 */

namespace Illuminate\View;

use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\View\Factory as FactoryContract;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Illuminate\View\Engines\EngineResolver;
use InvalidArgumentException;

class Factory implements FactoryContract
{
    use Macroable,
        Concerns\ManagesComponents,
        Concerns\ManagesEvents,
        Concerns\ManagesLayouts,
        Concerns\ManagesLoops,
        Concerns\ManagesStacks,
        Concerns\ManagesTranslations;

    /**
     * The engine implementation.
	 * 引擎实现
     *
     * @var \Illuminate\View\Engines\EngineResolver
     */
    protected $engines;

    /**
     * The view finder implementation.
	 * 视图寻找实现
     *
     * @var \Illuminate\View\ViewFinderInterface
     */
    protected $finder;

    /**
     * The event dispatcher instance.
	 * 事件调度实例
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * The IoC container instance.
	 * 容器，控制反转
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * Data that should be available to all templates.
	 * 应该对所有模板可用的数据
     *
     * @var array
     */
    protected $shared = [];

    /**
     * The extension to engine bindings.
	 * 绑定后缀
     *
     * @var array
     */
    protected $extensions = [
        'blade.php' => 'blade',
        'php' => 'php',
        'css' => 'file',
        'html' => 'file',
    ];

    /**
     * The view composer events.
	 * 视图编写器事件
     *
     * @var array
     */
    protected $composers = [];

    /**
     * The number of active rendering operations.
	 * 活动呈现操作的数量
     *
     * @var int
     */
    protected $renderCount = 0;

    /**
     * Create a new view factory instance.
	 * 创建新的视图工厂实例
     *
     * @param  \Illuminate\View\Engines\EngineResolver  $engines
     * @param  \Illuminate\View\ViewFinderInterface  $finder
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function __construct(EngineResolver $engines, ViewFinderInterface $finder, Dispatcher $events)
    {
        $this->finder = $finder;
        $this->events = $events;
        $this->engines = $engines;

        $this->share('__env', $this);
    }

    /**
     * Get the evaluated view contents for the given view.
	 * 得到给定视图的求值视图内容
     *
     * @param  string  $path
     * @param  \Illuminate\Contracts\Support\Arrayable|array  $data
     * @param  array  $mergeData
     * @return \Illuminate\Contracts\View\View
     */
    public function file($path, $data = [], $mergeData = [])
    {
        $data = array_merge($mergeData, $this->parseData($data));

        return tap($this->viewInstance($path, $path, $data), function ($view) {
            $this->callCreator($view);
        });
    }

    /**
     * Get the evaluated view contents for the given view.
	 * 得到给定视图的求值视图内容
     *
     * @param  string  $view
     * @param  \Illuminate\Contracts\Support\Arrayable|array  $data
     * @param  array  $mergeData
     * @return \Illuminate\Contracts\View\View
     */
    public function make($view, $data = [], $mergeData = [])
    {
        $path = $this->finder->find(
            $view = $this->normalizeName($view)
        );

        // Next, we will create the view instance and call the view creator for the view
        // which can set any data, etc. Then we will return the view instance back to
        // the caller for rendering or performing other view manipulations on this.
		// 接下来，我们将创建视图实例并调用视图的视图创建者，该视图可以设置任何数据等。
		// 然后，我们将把视图实例返回给调用者，以便对其进行渲染或执行其他视图操作。
        $data = array_merge($mergeData, $this->parseData($data));

        return tap($this->viewInstance($view, $path, $data), function ($view) {
            $this->callCreator($view);
        });
    }

    /**
     * Get the first view that actually exists from the given list.
	 * 得到实际存在的第一个视图从给定列表中
     *
     * @param  array  $views
     * @param  \Illuminate\Contracts\Support\Arrayable|array  $data
     * @param  array  $mergeData
     * @return \Illuminate\Contracts\View\View
     *
     * @throws \InvalidArgumentException
     */
    public function first(array $views, $data = [], $mergeData = [])
    {
        $view = Arr::first($views, function ($view) {
            return $this->exists($view);
        });

        if (! $view) {
            throw new InvalidArgumentException('None of the views in the given array exist.');
        }

        return $this->make($view, $data, $mergeData);
    }

    /**
     * Get the rendered content of the view based on a given condition.
	 * 得到视图的呈现内容根据给定条件
     *
     * @param  bool  $condition
     * @param  string  $view
     * @param  \Illuminate\Contracts\Support\Arrayable|array  $data
     * @param  array  $mergeData
     * @return string
     */
    public function renderWhen($condition, $view, $data = [], $mergeData = [])
    {
        if (! $condition) {
            return '';
        }

        return $this->make($view, $this->parseData($data), $mergeData)->render();
    }

    /**
     * Get the rendered contents of a partial from a loop.
	 * 从循环中获取部分的渲染内容
     *
     * @param  string  $view
     * @param  array  $data
     * @param  string  $iterator
     * @param  string  $empty
     * @return string
     */
    public function renderEach($view, $data, $iterator, $empty = 'raw|')
    {
        $result = '';

        // If is actually data in the array, we will loop through the data and append
        // an instance of the partial view to the final result HTML passing in the
        // iterated value of this data array, allowing the views to access them.
		// 如果数组中实际上是数据，我们将循环遍历数据，并将部分视图的实例附加到最终结果HTML中，
		// 传递此数据数组的迭代值，允许视图访问它们。
        if (count($data) > 0) {
            foreach ($data as $key => $value) {
                $result .= $this->make(
                    $view, ['key' => $key, $iterator => $value]
                )->render();
            }
        }

        // If there is no data in the array, we will render the contents of the empty
        // view. Alternatively, the "empty view" could be a raw string that begins
        // with "raw|" for convenience and to let this know that it is a string.
		// 如果数组中没有数据，我们将呈现空视图的内容。
		// 或者，为了方便起见，"空视图"可以是一个以"raw|"开头的原始字符串，并让它知道它是一个字符串。
        else {
            $result = Str::startsWith($empty, 'raw|')
                        ? substr($empty, 4)
                        : $this->make($empty)->render();
        }

        return $result;
    }

    /**
     * Normalize a view name.
	 * 规范化视图名称
     *
     * @param  string  $name
     * @return string
     */
    protected function normalizeName($name)
    {
        return ViewName::normalize($name);
    }

    /**
     * Parse the given data into a raw array.
	 * 解析给定的数据为原始数组
     *
     * @param  mixed  $data
     * @return array
     */
    protected function parseData($data)
    {
        return $data instanceof Arrayable ? $data->toArray() : $data;
    }

    /**
     * Create a new view instance from the given arguments.
	 * 创建新的视图实例根据给定的参数
     *
     * @param  string  $view
     * @param  string  $path
     * @param  \Illuminate\Contracts\Support\Arrayable|array  $data
     * @return \Illuminate\Contracts\View\View
     */
    protected function viewInstance($view, $path, $data)
    {
        return new View($this, $this->getEngineFromPath($path), $view, $path, $data);
    }

    /**
     * Determine if a given view exists.
	 * 确定给定视图是否存在
     *
     * @param  string  $view
     * @return bool
     */
    public function exists($view)
    {
        try {
            $this->finder->find($view);
        } catch (InvalidArgumentException $e) {
            return false;
        }

        return true;
    }

    /**
     * Get the appropriate view engine for the given path.
	 * 得到给定路径的适当视图引擎
     *
     * @param  string  $path
     * @return \Illuminate\Contracts\View\Engine
     *
     * @throws \InvalidArgumentException
     */
    public function getEngineFromPath($path)
    {
        if (! $extension = $this->getExtension($path)) {
            throw new InvalidArgumentException("Unrecognized extension in file: {$path}");
        }

        $engine = $this->extensions[$extension];

        return $this->engines->resolve($engine);
    }

    /**
     * Get the extension used by the view file.
	 * 得到视图文件使用的扩展名
     *
     * @param  string  $path
     * @return string
     */
    protected function getExtension($path)
    {
        $extensions = array_keys($this->extensions);

        return Arr::first($extensions, function ($value) use ($path) {
            return Str::endsWith($path, '.'.$value);
        });
    }

    /**
     * Add a piece of shared data to the environment.
	 * 添加一段共享数据至环境中
     *
     * @param  array|string  $key
     * @param  mixed|null  $value
     * @return mixed
     */
    public function share($key, $value = null)
    {
        $keys = is_array($key) ? $key : [$key => $value];

        foreach ($keys as $key => $value) {
            $this->shared[$key] = $value;
        }

        return $value;
    }

    /**
     * Increment the rendering counter.
	 * 增加呈现计数器
     *
     * @return void
     */
    public function incrementRender()
    {
        $this->renderCount++;
    }

    /**
     * Decrement the rendering counter.
	 * 增加呈现计数器
     *
     * @return void
     */
    public function decrementRender()
    {
        $this->renderCount--;
    }

    /**
     * Check if there are no active render operations.
	 * 检查是否没有活动的渲染操作
     *
     * @return bool
     */
    public function doneRendering()
    {
        return $this->renderCount == 0;
    }

    /**
     * Add a location to the array of view locations.
	 * 添加一个位置向视图位置数组
     *
     * @param  string  $location
     * @return void
     */
    public function addLocation($location)
    {
        $this->finder->addLocation($location);
    }

    /**
     * Add a new namespace to the loader.
	 * 添加一个新的命名空间至加载器
     *
     * @param  string  $namespace
     * @param  string|array  $hints
     * @return $this
     */
    public function addNamespace($namespace, $hints)
    {
        $this->finder->addNamespace($namespace, $hints);

        return $this;
    }

    /**
     * Prepend a new namespace to the loader.
	 * 添加一个新的命名空间至加载器
     *
     * @param  string  $namespace
     * @param  string|array  $hints
     * @return $this
     */
    public function prependNamespace($namespace, $hints)
    {
        $this->finder->prependNamespace($namespace, $hints);

        return $this;
    }

    /**
     * Replace the namespace hints for the given namespace.
	 * 替换给定名称空间的名称空间提示
     *
     * @param  string  $namespace
     * @param  string|array  $hints
     * @return $this
     */
    public function replaceNamespace($namespace, $hints)
    {
        $this->finder->replaceNamespace($namespace, $hints);

        return $this;
    }

    /**
     * Register a valid view extension and its engine.
	 * 注册一个有效的视图扩展及其引擎
     *
     * @param  string  $extension
     * @param  string  $engine
     * @param  \Closure|null  $resolver
     * @return void
     */
    public function addExtension($extension, $engine, $resolver = null)
    {
        $this->finder->addExtension($extension);

        if (isset($resolver)) {
            $this->engines->register($engine, $resolver);
        }

        unset($this->extensions[$extension]);

        $this->extensions = array_merge([$extension => $engine], $this->extensions);
    }

    /**
     * Flush all of the factory state like sections and stacks.
	 * 刷新所有工厂状态，如节和堆栈。
     *
     * @return void
     */
    public function flushState()
    {
        $this->renderCount = 0;

        $this->flushSections();
        $this->flushStacks();
    }

    /**
     * Flush all of the section contents if done rendering.
	 * 如果完成呈现，则刷新所有节内容。
     *
     * @return void
     */
    public function flushStateIfDoneRendering()
    {
        if ($this->doneRendering()) {
            $this->flushState();
        }
    }

    /**
     * Get the extension to engine bindings.
	 * 得到引擎绑定的扩展
     *
     * @return array
     */
    public function getExtensions()
    {
        return $this->extensions;
    }

    /**
     * Get the engine resolver instance.
	 * 得到引擎解析器实例
     *
     * @return \Illuminate\View\Engines\EngineResolver
     */
    public function getEngineResolver()
    {
        return $this->engines;
    }

    /**
     * Get the view finder instance.
	 * 得到视图查找实例
     *
     * @return \Illuminate\View\ViewFinderInterface
     */
    public function getFinder()
    {
        return $this->finder;
    }

    /**
     * Set the view finder instance.
	 * 设置视图查找实例
     *
     * @param  \Illuminate\View\ViewFinderInterface  $finder
     * @return void
     */
    public function setFinder(ViewFinderInterface $finder)
    {
        $this->finder = $finder;
    }

    /**
     * Flush the cache of views located by the finder.
	 * 刷新查找器定位的视图的缓存
     *
     * @return void
     */
    public function flushFinderCache()
    {
        $this->getFinder()->flush();
    }

    /**
     * Get the event dispatcher instance.
	 * 得到事件调度程序实例
     *
     * @return \Illuminate\Contracts\Events\Dispatcher
     */
    public function getDispatcher()
    {
        return $this->events;
    }

    /**
     * Set the event dispatcher instance.
	 * 设置事件调度程序实例
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function setDispatcher(Dispatcher $events)
    {
        $this->events = $events;
    }

    /**
     * Get the IoC container instance.
	 * 得到IoC容器实例
     *
     * @return \Illuminate\Contracts\Container\Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Set the IoC container instance.
	 * 设置容器实例
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return void
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Get an item from the shared data.
	 * 得到共享项
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function shared($key, $default = null)
    {
        return Arr::get($this->shared, $key, $default);
    }

    /**
     * Get all of the shared data for the environment.
	 * 得到所有共享数据
     *
     * @return array
     */
    public function getShared()
    {
        return $this->shared;
    }
}
