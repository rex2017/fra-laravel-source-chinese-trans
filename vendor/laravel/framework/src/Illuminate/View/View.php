<?php
/**
 * 视图，核心类
 */

namespace Illuminate\View;

use ArrayAccess;
use BadMethodCallException;
use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Support\MessageProvider;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\View\Engine;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Throwable;

class View implements ArrayAccess, Htmlable, ViewContract
{
    use Macroable {
        __call as macroCall;
    }

    /**
     * The view factory instance.
	 * 视图工厂实例
     *
     * @var \Illuminate\View\Factory
     */
    protected $factory;

    /**
     * The engine implementation.
	 * 引擎实现
     *
     * @var \Illuminate\Contracts\View\Engine
     */
    protected $engine;

    /**
     * The name of the view.
	 * 视图名称
     *
     * @var string
     */
    protected $view;

    /**
     * The array of view data.
	 * 数据
     *
     * @var array
     */
    protected $data;

    /**
     * The path to the view file.
	 * 视图文件路径
     *
     * @var string
     */
    protected $path;

    /**
     * Create a new view instance.
	 * 创建新的视图实例
     *
     * @param  \Illuminate\View\Factory  $factory
     * @param  \Illuminate\Contracts\View\Engine  $engine
     * @param  string  $view
     * @param  string  $path
     * @param  mixed  $data
     * @return void
     */
    public function __construct(Factory $factory, Engine $engine, $view, $path, $data = [])
    {
        $this->view = $view;
        $this->path = $path;
        $this->engine = $engine;
        $this->factory = $factory;

        $this->data = $data instanceof Arrayable ? $data->toArray() : (array) $data;
    }

    /**
     * Get the string contents of the view.
	 * 得到字符串内容
     *
     * @param  callable|null  $callback
     * @return array|string
     *
     * @throws \Throwable
     */
    public function render(callable $callback = null)
    {
        try {
            $contents = $this->renderContents();

            $response = isset($callback) ? $callback($this, $contents) : null;

            // Once we have the contents of the view, we will flush the sections if we are
            // done rendering all views so that there is nothing left hanging over when
            // another view gets rendered in the future by the application developer.
			// 一旦我们有了视图的内容，如果我们完成了所有视图的渲染，我们将刷新这些部分，
			// 这样当应用程序开发人员将来渲染另一个视图时，就不会有任何东西悬而未决。
            $this->factory->flushStateIfDoneRendering();

            return ! is_null($response) ? $response : $contents;
        } catch (Exception $e) {
            $this->factory->flushState();

            throw $e;
        } catch (Throwable $e) {
            $this->factory->flushState();

            throw $e;
        }
    }

    /**
     * Get the contents of the view instance.
	 * 得到视图实例内容
     *
     * @return string
     */
    protected function renderContents()
    {
        // We will keep track of the amount of views being rendered so we can flush
        // the section after the complete rendering operation is done. This will
        // clear out the sections for any separate views that may be rendered.
		// 我们将跟踪渲染的视图数量，以便在完成完整的渲染操作后刷新该部分。
		// 这将清除可能呈现的任何单独视图的部分。
        $this->factory->incrementRender();

        $this->factory->callComposer($this);

        $contents = $this->getContents();

        // Once we've finished rendering the view, we'll decrement the render count
        // so that each sections get flushed out next time a view is created and
        // no old sections are staying around in the memory of an environment.
		// 一旦我们完成了视图的渲染，我们将减少渲染计数，这样下次创建视图时，
		// 每个部分都会被清空，并且没有旧的部分留在环境的内存中。
        $this->factory->decrementRender();

        return $contents;
    }

    /**
     * Get the evaluated contents of the view.
	 * 得到视图请求内容
     *
     * @return string
     */
    protected function getContents()
    {
        return $this->engine->get($this->path, $this->gatherData());
    }

    /**
     * Get the data bound to the view instance.
	 * 得到绑定到视图实例的数据
     *
     * @return array
     */
    public function gatherData()
    {
        $data = array_merge($this->factory->getShared(), $this->data);

        foreach ($data as $key => $value) {
            if ($value instanceof Renderable) {
                $data[$key] = $value->render();
            }
        }

        return $data;
    }

    /**
     * Get the sections of the rendered view.
	 * 得到渲染视图的部分
     *
     * @return array
     *
     * @throws \Throwable
     */
    public function renderSections()
    {
        return $this->render(function () {
            return $this->factory->getSections();
        });
    }

    /**
     * Add a piece of data to the view.
	 * 向视图添加一段数据
     *
     * @param  string|array  $key
     * @param  mixed  $value
     * @return $this
     */
    public function with($key, $value = null)
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }

    /**
     * Add a view instance to the view data.
	 * 添加一段实例至视图
     *
     * @param  string  $key
     * @param  string  $view
     * @param  array  $data
     * @return $this
     */
    public function nest($key, $view, array $data = [])
    {
        return $this->with($key, $this->factory->make($view, $data));
    }

    /**
     * Add validation errors to the view.
	 * 添加验证错误到视图中
     *
     * @param  \Illuminate\Contracts\Support\MessageProvider|array  $provider
     * @return $this
     */
    public function withErrors($provider)
    {
        $this->with('errors', $this->formatErrors($provider));

        return $this;
    }

    /**
     * Format the given message provider into a MessageBag.
	 * 格式化给定的消息提供程序为MessageBag
     *
     * @param  \Illuminate\Contracts\Support\MessageProvider|array  $provider
     * @return \Illuminate\Support\MessageBag
     */
    protected function formatErrors($provider)
    {
        return $provider instanceof MessageProvider
                        ? $provider->getMessageBag() : new MessageBag((array) $provider);
    }

    /**
     * Get the name of the view.
	 * 得到视图名称
     *
     * @return string
     */
    public function name()
    {
        return $this->getName();
    }

    /**
     * Get the name of the view.
	 * 得到视图名称
     *
     * @return string
     */
    public function getName()
    {
        return $this->view;
    }

    /**
     * Get the array of view data.
	 * 得到视图数据
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Get the path to the view file.
	 * 得到视图文件路径
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set the path to the view.
	 * 设置视图路径
     *
     * @param  string  $path
     * @return void
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * Get the view factory instance.
	 * 得到视图工作实例
     *
     * @return \Illuminate\View\Factory
     */
    public function getFactory()
    {
        return $this->factory;
    }

    /**
     * Get the view's rendering engine.
	 * 得到视图的渲染引擎
     *
     * @return \Illuminate\Contracts\View\Engine
     */
    public function getEngine()
    {
        return $this->engine;
    }

    /**
     * Determine if a piece of data is bound.
	 * 确定是否绑定了一段数据
     *
     * @param  string  $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Get a piece of bound data to the view.
	 * 得到一段绑定到视图的数据
     *
     * @param  string  $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->data[$key];
    }

    /**
     * Set a piece of data on the view.
	 * 设置一段数据在视图上
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        $this->with($key, $value);
    }

    /**
     * Unset a piece of data from the view.
	 * 取消设置一段数据从视图中
     *
     * @param  string  $key
     * @return void
     */
    public function offsetUnset($key)
    {
        unset($this->data[$key]);
    }

    /**
     * Get a piece of data from the view.
	 * 得到一段数据从视图
     *
     * @param  string  $key
     * @return mixed
     */
    public function &__get($key)
    {
        return $this->data[$key];
    }

    /**
     * Set a piece of data on the view.
	 * 设置一段数据在视图上
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->with($key, $value);
    }

    /**
     * Check if a piece of data is bound to the view.
	 * 检查是否有数据块绑定到视图
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->data[$key]);
    }

    /**
     * Remove a piece of bound data from the view.
	 * 删除一段绑定数据从视图中
     *
     * @param  string  $key
     * @return void
     */
    public function __unset($key)
    {
        unset($this->data[$key]);
    }

    /**
     * Dynamically bind parameters to the view.
	 * 动态地将参数绑定到视图
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return \Illuminate\View\View
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        if (! Str::startsWith($method, 'with')) {
            throw new BadMethodCallException(sprintf(
                'Method %s::%s does not exist.', static::class, $method
            ));
        }

        return $this->with(Str::camel(substr($method, 4)), $parameters[0]);
    }

    /**
     * Get content as a string of HTML.
	 * 得到HTMl内容
     *
     * @return string
     */
    public function toHtml()
    {
        return $this->render();
    }

    /**
     * Get the string contents of the view.
	 * 得到视图的字符串内容
     *
     * @return string
     *
     * @throws \Throwable
     */
    public function __toString()
    {
        return $this->render();
    }
}
