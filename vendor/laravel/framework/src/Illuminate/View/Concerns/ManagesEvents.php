<?php
/**
 * 视图，管理事件
 */

namespace Illuminate\View\Concerns;

use Closure;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Str;

trait ManagesEvents
{
    /**
     * Register a view creator event.
	 * 注册一个视图创建者事件
     *
     * @param  array|string  $views
     * @param  \Closure|string  $callback
     * @return array
     */
    public function creator($views, $callback)
    {
        $creators = [];

        foreach ((array) $views as $view) {
            $creators[] = $this->addViewEvent($view, $callback, 'creating: ');
        }

        return $creators;
    }

    /**
     * Register multiple view composers via an array.
	 * 通过一个数组注册多个视图composers
     *
     * @param  array  $composers
     * @return array
     */
    public function composers(array $composers)
    {
        $registered = [];

        foreach ($composers as $callback => $views) {
            $registered = array_merge($registered, $this->composer($views, $callback));
        }

        return $registered;
    }

    /**
     * Register a view composer event.
	 * 注册一个视图编写器事件
     *
     * @param  array|string  $views
     * @param  \Closure|string  $callback
     * @return array
     */
    public function composer($views, $callback)
    {
        $composers = [];

        foreach ((array) $views as $view) {
            $composers[] = $this->addViewEvent($view, $callback, 'composing: ');
        }

        return $composers;
    }

    /**
     * Add an event for a given view.
	 * 添加事件为给定视图
     *
     * @param  string  $view
     * @param  \Closure|string  $callback
     * @param  string  $prefix
     * @return \Closure|null
     */
    protected function addViewEvent($view, $callback, $prefix = 'composing: ')
    {
        $view = $this->normalizeName($view);

        if ($callback instanceof Closure) {
            $this->addEventListener($prefix.$view, $callback);

            return $callback;
        } elseif (is_string($callback)) {
            return $this->addClassEvent($view, $callback, $prefix);
        }
    }

    /**
     * Register a class based view composer.
	 * 注册一个基于类的视图编写器
     *
     * @param  string  $view
     * @param  string  $class
     * @param  string  $prefix
     * @return \Closure
     */
    protected function addClassEvent($view, $class, $prefix)
    {
        $name = $prefix.$view;

        // When registering a class based view "composer", we will simply resolve the
        // classes from the application IoC container then call the compose method
        // on the instance. This allows for convenient, testable view composers.
		// 在注册基于类的视图"composer"时，我们只需从应用程序IoC容器中解析类，然后调用实例上的compose方法。
		// 这允许方便、可测试的视图编辑器。
        $callback = $this->buildClassEventCallback(
            $class, $prefix
        );

        $this->addEventListener($name, $callback);

        return $callback;
    }

    /**
     * Build a class based container callback Closure.
	 * 构建一个基于类的容器回调闭包
     *
     * @param  string  $class
     * @param  string  $prefix
     * @return \Closure
     */
    protected function buildClassEventCallback($class, $prefix)
    {
        [$class, $method] = $this->parseClassEvent($class, $prefix);

        // Once we have the class and method name, we can build the Closure to resolve
        // the instance out of the IoC container and call the method on it with the
        // given arguments that are passed to the Closure as the composer's data.
		// 一旦我们有了类名和方法名，我们就可以构建闭包来从IoC容器中解析实例，
		// 并使用传递给闭包的给定参数作为编写器的数据来调用它的方法。
        return function () use ($class, $method) {
            return $this->container->make($class)->{$method}(...func_get_args());
        };
    }

    /**
     * Parse a class based composer name.
	 * 解析基于编写器名称的类
     *
     * @param  string  $class
     * @param  string  $prefix
     * @return array
     */
    protected function parseClassEvent($class, $prefix)
    {
        return Str::parseCallback($class, $this->classEventMethodForPrefix($prefix));
    }

    /**
     * Determine the class event method based on the given prefix.
	 * 确定类事件方法根据给定的前缀
     *
     * @param  string  $prefix
     * @return string
     */
    protected function classEventMethodForPrefix($prefix)
    {
        return Str::contains($prefix, 'composing') ? 'compose' : 'create';
    }

    /**
     * Add a listener to the event dispatcher.
	 * 添加侦听器向事件调度程序
     *
     * @param  string  $name
     * @param  \Closure  $callback
     * @return void
     */
    protected function addEventListener($name, $callback)
    {
        if (Str::contains($name, '*')) {
            $callback = function ($name, array $data) use ($callback) {
                return $callback($data[0]);
            };
        }

        $this->events->listen($name, $callback);
    }

    /**
     * Call the composer for a given view.
	 * 调用给定视图的composer
     *
     * @param  \Illuminate\Contracts\View\View  $view
     * @return void
     */
    public function callComposer(ViewContract $view)
    {
        $this->events->dispatch('composing: '.$view->name(), [$view]);
    }

    /**
     * Call the creator for a given view.
	 * 调用给定视图的创建者
     *
     * @param  \Illuminate\Contracts\View\View  $view
     * @return void
     */
    public function callCreator(ViewContract $view)
    {
        $this->events->dispatch('creating: '.$view->name(), [$view]);
    }
}
