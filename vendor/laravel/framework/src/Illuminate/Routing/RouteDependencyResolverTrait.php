<?php
/**
 * 路由依赖解析特性
 */

namespace Illuminate\Routing;

use Illuminate\Support\Arr;
use Illuminate\Support\Reflector;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;

trait RouteDependencyResolverTrait
{
    /**
     * Resolve the object method's type-hinted dependencies.
	 * 解析对象方法的类型暗示依赖项
     *
     * @param  array  $parameters
     * @param  object  $instance
     * @param  string  $method
     * @return array
     */
    protected function resolveClassMethodDependencies(array $parameters, $instance, $method)
    {
        if (! method_exists($instance, $method)) {
            return $parameters;
        }

        return $this->resolveMethodDependencies(
            $parameters, new ReflectionMethod($instance, $method)
        );
    }

    /**
     * Resolve the given method's type-hinted dependencies.
	 * 解析给定方法的类型提示依赖项
     *
     * @param  array  $parameters
     * @param  \ReflectionFunctionAbstract  $reflector
     * @return array
     */
    public function resolveMethodDependencies(array $parameters, ReflectionFunctionAbstract $reflector)
    {
        $instanceCount = 0;

        $values = array_values($parameters);

        foreach ($reflector->getParameters() as $key => $parameter) {
            $instance = $this->transformDependency(
                $parameter, $parameters
            );

            if (! is_null($instance)) {
                $instanceCount++;

                $this->spliceIntoParameters($parameters, $key, $instance);
            } elseif (! isset($values[$key - $instanceCount]) &&
                      $parameter->isDefaultValueAvailable()) {
                $this->spliceIntoParameters($parameters, $key, $parameter->getDefaultValue());
            }
        }

        return $parameters;
    }

    /**
     * Attempt to transform the given parameter into a class instance.
	 * 尝试将给定的参数转换为类实例
     *
     * @param  \ReflectionParameter  $parameter
     * @param  array  $parameters
     * @return mixed
     */
    protected function transformDependency(ReflectionParameter $parameter, $parameters)
    {
        $className = Reflector::getParameterClassName($parameter);

        // If the parameter has a type-hinted class, we will check to see if it is already in
        // the list of parameters. If it is we will just skip it as it is probably a model
        // binding and we do not want to mess with those; otherwise, we resolve it here.
		// 如果参数有一个类型提示类，我们将检查它是否已经在参数列表中。
		// 如果是这样，我们就跳过它，因为它可能是一个模型绑定，我们不想弄乱这些；否则，我们在这里解决。
        if ($className && ! $this->alreadyInParameters($className, $parameters)) {
            return $parameter->isDefaultValueAvailable()
                ? $parameter->getDefaultValue()
                : $this->container->make($className);
        }
    }

    /**
     * Determine if an object of the given class is in a list of parameters.
	 * 确定给定类的对象是否在参数列表中
     *
     * @param  string  $class
     * @param  array  $parameters
     * @return bool
     */
    protected function alreadyInParameters($class, array $parameters)
    {
        return ! is_null(Arr::first($parameters, function ($value) use ($class) {
            return $value instanceof $class;
        }));
    }

    /**
     * Splice the given value into the parameter list.
	 * 拼接给定的值到参数列表中
     *
     * @param  array  $parameters
     * @param  string  $offset
     * @param  mixed  $value
     * @return void
     */
    protected function spliceIntoParameters(array &$parameters, $offset, $value)
    {
        array_splice(
            $parameters, $offset, 0, [$value]
        );
    }
}
