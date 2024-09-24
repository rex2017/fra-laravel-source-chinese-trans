<?php
/**
 * 契约，大门
 */

namespace Illuminate\Contracts\Auth\Access;

interface Gate
{
    /**
     * Determine if a given ability has been defined.
	 * 确定是否已经定义了给定的能力
     *
     * @param  string  $ability
     * @return bool
     */
    public function has($ability);

    /**
     * Define a new ability.
	 * 定义新的能力
     *
     * @param  string  $ability
     * @param  callable|string  $callback
     * @return $this
     */
    public function define($ability, $callback);

    /**
     * Define abilities for a resource.
	 * 定义资源的能力
     *
     * @param  string  $name
     * @param  string  $class
     * @param  array|null  $abilities
     * @return $this
     */
    public function resource($name, $class, array $abilities = null);

    /**
     * Define a policy class for a given class type.
	 * 定义策略类为给定的类类型
     *
     * @param  string  $class
     * @param  string  $policy
     * @return $this
     */
    public function policy($class, $policy);

    /**
     * Register a callback to run before all Gate checks.
	 * 注册一个回调以便在所有Gate检查之前运行
     *
     * @param  callable  $callback
     * @return $this
     */
    public function before(callable $callback);

    /**
     * Register a callback to run after all Gate checks.
	 * 注册一个回调以便在所有Gate检查之后运行
     *
     * @param  callable  $callback
     * @return $this
     */
    public function after(callable $callback);

    /**
     * Determine if the given ability should be granted for the current user.
	 * 确定是否应该为当前用户授予给定的能力
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function allows($ability, $arguments = []);

    /**
     * Determine if the given ability should be denied for the current user.
	 * 确定当前用户是否应该拒绝给定的能力
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function denies($ability, $arguments = []);

    /**
     * Determine if all of the given abilities should be granted for the current user.
	 * 确定是否应该为当前用户授予所有给定的能力
     *
     * @param  iterable|string  $abilities
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function check($abilities, $arguments = []);

    /**
     * Determine if any one of the given abilities should be granted for the current user.
	 * 确定是否应该为当前用户授予给定的任何一种能力
     *
     * @param  iterable|string  $abilities
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function any($abilities, $arguments = []);

    /**
     * Determine if the given ability should be granted for the current user.
	 * 确定是否应该为当前用户授予给定的能力
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return \Illuminate\Auth\Access\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorize($ability, $arguments = []);

    /**
     * Inspect the user for the given ability.
	 * 检查用户是否具有给定的能力
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return \Illuminate\Auth\Access\Response
     */
    public function inspect($ability, $arguments = []);

    /**
     * Get the raw result from the authorization callback.
	 * 得到原始结果从授权回调
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return mixed
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function raw($ability, $arguments = []);

    /**
     * Get a policy instance for a given class.
	 * 得到给定类的策略实例
     *
     * @param  object|string  $class
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function getPolicyFor($class);

    /**
     * Get a guard instance for the given user.
	 * 得到给定用户的保护实例
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|mixed  $user
     * @return static
     */
    public function forUser($user);

    /**
     * Get all of the defined abilities.
	 * 得到所有已定义的能力
     *
     * @return array
     */
    public function abilities();
}
