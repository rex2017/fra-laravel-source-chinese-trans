<?php
/**
 * 路由绑定
 */

namespace Illuminate\Routing;

use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

class RouteBinding
{
    /**
     * Create a Route model binding for a given callback.
	 * 创建路由模型绑定
     *
     * @param  \Illuminate\Container\Container  $container
     * @param  \Closure|string  $binder
     * @return \Closure
     */
    public static function forCallback($container, $binder)
    {
        if (is_string($binder)) {
            return static::createClassBinding($container, $binder);
        }

        return $binder;
    }

    /**
     * Create a class based binding using the IoC container.
	 * 创建基于类的绑定
     *
     * @param  \Illuminate\Container\Container  $container
     * @param  string  $binding
     * @return \Closure
     */
    protected static function createClassBinding($container, $binding)
    {
        return function ($value, $route) use ($container, $binding) {
            // If the binding has an @ sign, we will assume it's being used to delimit
            // the class name from the bind method name. This allows for bindings
            // to run multiple bind methods in a single class for convenience.
			// 如果绑定有@符号，我们将假设它被用来将类名与绑定方法名分隔开来。
			// 为了方便起见，这允许绑定在单个类中运行多个绑定方法。
            [$class, $method] = Str::parseCallback($binding, 'bind');

            $callable = [$container->make($class), $method];

            return $callable($value, $route);
        };
    }

    /**
     * Create a Route model binding for a model.
	 * 创建新的路由绑定模型
     *
     * @param  \Illuminate\Container\Container  $container
     * @param  string  $class
     * @param  \Closure|null  $callback
     * @return \Closure
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public static function forModel($container, $class, $callback = null)
    {
        return function ($value) use ($container, $class, $callback) {
            if (is_null($value)) {
                return;
            }

            // For model binders, we will attempt to retrieve the models using the first
            // method on the model instance. If we cannot retrieve the models we'll
            // throw a not found exception otherwise we will return the instance.
			// 对于模型绑定器，我们将尝试在模型实例上使用第一种方法检索模型。
			// 如果我们无法检索模型，我们将抛出一个未找到的异常，否则我们将返回实例。
            $instance = $container->make($class);

            if ($model = $instance->resolveRouteBinding($value)) {
                return $model;
            }

            // If a callback was supplied to the method we will call that to determine
            // what we should do when the model is not found. This just gives these
            // developer a little greater flexibility to decide what will happen.
			// 如果向该方法提供了回调，我们将调用该回调来确定在找不到模型时应该做什么。
			// 这只会给这些开发人员更大的灵活性来决定会发生什么。
            if ($callback instanceof Closure) {
                return $callback($value);
            }

            throw (new ModelNotFoundException)->setModel($class);
        };
    }
}
