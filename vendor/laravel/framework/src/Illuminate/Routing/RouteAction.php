<?php
/**
 * 路由动作
 */

namespace Illuminate\Routing;

use Illuminate\Support\Arr;
use Illuminate\Support\Reflector;
use Illuminate\Support\Str;
use LogicException;
use UnexpectedValueException;

class RouteAction
{
    /**
     * Parse the given action into an array.
	 * 解析给定的动作
     *
     * @param  string  $uri
     * @param  mixed  $action
     * @return array
     */
    public static function parse($uri, $action)
    {
        // If no action is passed in right away, we assume the user will make use of
        // fluent routing. In that case, we set a default closure, to be executed
        // if the user never explicitly sets an action to handle the given uri.
		// 如果没有立即传递任何操作，我们假设用户将使用流畅的路由。
		// 在这种情况下，我们设置了一个默认闭包，如果用户从未明确设置操作来处理给定的uri，则执行该闭包。
        if (is_null($action)) {
            return static::missingAction($uri);
        }

        // If the action is already a Closure instance, we will just set that instance
        // as the "uses" property, because there is nothing else we need to do when
        // it is available. Otherwise we will need to find it in the action list.
		// 如果该动作已经是一个闭包实例，我们只需将该实例设置为"uses"属性，因为当它可用时，
		// 我们不需要做任何其他事情。否则，我们需要在行动列表中找到它。
        if (Reflector::isCallable($action, true)) {
            return ! is_array($action) ? ['uses' => $action] : [
                'uses' => $action[0].'@'.$action[1],
                'controller' => $action[0].'@'.$action[1],
            ];
        }

        // If no "uses" property has been set, we will dig through the array to find a
        // Closure instance within this list. We will set the first Closure we come
        // across into the "uses" property that will get fired off by this route.
		// 如果没有设置"uses"属性，我们将在数组中查找此列表中的Closure实例。
		// 我们将把遇到的第一个闭包设置到将通过此路径触发的"uses"属性中。
        elseif (! isset($action['uses'])) {
            $action['uses'] = static::findCallable($action);
        }

        if (is_string($action['uses']) && ! Str::contains($action['uses'], '@')) {
            $action['uses'] = static::makeInvokable($action['uses']);
        }

        return $action;
    }

    /**
     * Get an action for a route that has no action.
	 * 获取一个动作为没有动作的路由
     *
     * @param  string  $uri
     * @return array
     *
     * @throws \LogicException
     */
    protected static function missingAction($uri)
    {
        return ['uses' => function () use ($uri) {
            throw new LogicException("Route for [{$uri}] has no action.");
        }];
    }

    /**
     * Find the callable in an action array.
	 * 查找可调用对象
     *
     * @param  array  $action
     * @return callable
     */
    protected static function findCallable(array $action)
    {
        return Arr::first($action, function ($value, $key) {
            return Reflector::isCallable($value) && is_numeric($key);
        });
    }

    /**
     * Make an action for an invokable controller.
	 * 创建一个操作为可调用控制器
     *
     * @param  string  $action
     * @return string
     *
     * @throws \UnexpectedValueException
     */
    protected static function makeInvokable($action)
    {
        if (! method_exists($action, '__invoke')) {
            throw new UnexpectedValueException("Invalid route action: [{$action}].");
        }

        return $action.'@__invoke';
    }
}
