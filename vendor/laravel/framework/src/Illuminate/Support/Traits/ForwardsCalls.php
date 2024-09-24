<?php
/**
 * 支持，调用转发
 */

namespace Illuminate\Support\Traits;

use BadMethodCallException;
use Error;

trait ForwardsCalls
{
    /**
     * Forward a method call to the given object.
	 * 转发一个方法调用给给定对象
     *
     * @param  mixed  $object
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    protected function forwardCallTo($object, $method, $parameters)
    {
        try {
            return $object->{$method}(...$parameters);
        } catch (Error|BadMethodCallException $e) {
            $pattern = '~^Call to undefined method (?P<class>[^:]+)::(?P<method>[^\(]+)\(\)$~';

            if (! preg_match($pattern, $e->getMessage(), $matches)) {
                throw $e;
            }

            if ($matches['class'] != get_class($object) ||
                $matches['method'] != $method) {
                throw $e;
            }

            static::throwBadMethodCallException($method);
        }
    }

    /**
     * Throw a bad method call exception for the given method.
	 * 抛出一个坏的方法调用异常对给定的方法
     *
     * @param  string  $method
     * @return void
     *
     * @throws \BadMethodCallException
     */
    protected static function throwBadMethodCallException($method)
    {
        throw new BadMethodCallException(sprintf(
            'Call to undefined method %s::%s()', static::class, $method
        ));
    }
}
