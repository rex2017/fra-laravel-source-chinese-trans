<?php
/**
 * 身份，大门
 */

namespace Illuminate\Auth\Access;

use Exception;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionFunction;

class Gate implements GateContract
{
    use HandlesAuthorization;

    /**
     * The container instance.
	 * 容器实例
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * The user resolver callable.
	 * 可调用的用户解析器
     *
     * @var callable
     */
    protected $userResolver;

    /**
     * All of the defined abilities.
	 * 所有已定义的能力
     *
     * @var array
     */
    protected $abilities = [];

    /**
     * All of the defined policies.
	 * 所有已定义的策略
     *
     * @var array
     */
    protected $policies = [];

    /**
     * All of the registered before callbacks.
	 * 所有在回调之前注册的
     *
     * @var array
     */
    protected $beforeCallbacks = [];

    /**
     * All of the registered after callbacks.
	 * 所有在回调之后注册的
     *
     * @var array
     */
    protected $afterCallbacks = [];

    /**
     * All of the defined abilities using class@method notation.
	 * 所有已定义的能力都使用class@method符号
     *
     * @var array
     */
    protected $stringCallbacks = [];

    /**
     * The callback to be used to guess policy names.
	 * 用于猜测策略名称的回调
     *
     * @var callable|null
     */
    protected $guessPolicyNamesUsingCallback;

    /**
     * Create a new gate instance.
	 * 创建新的大门实例
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @param  callable  $userResolver
     * @param  array  $abilities
     * @param  array  $policies
     * @param  array  $beforeCallbacks
     * @param  array  $afterCallbacks
     * @param  callable|null  $guessPolicyNamesUsingCallback
     * @return void
     */
    public function __construct(Container $container, callable $userResolver, array $abilities = [],
                                array $policies = [], array $beforeCallbacks = [], array $afterCallbacks = [],
                                callable $guessPolicyNamesUsingCallback = null)
    {
        $this->policies = $policies;
        $this->container = $container;
        $this->abilities = $abilities;
        $this->userResolver = $userResolver;
        $this->afterCallbacks = $afterCallbacks;
        $this->beforeCallbacks = $beforeCallbacks;
        $this->guessPolicyNamesUsingCallback = $guessPolicyNamesUsingCallback;
    }

    /**
     * Determine if a given ability has been defined.
	 * 确定是否已经定义了给定的能力
     *
     * @param  string|array  $ability
     * @return bool
     */
    public function has($ability)
    {
        $abilities = is_array($ability) ? $ability : func_get_args();

        foreach ($abilities as $ability) {
            if (! isset($this->abilities[$ability])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Define a new ability.
	 * 定义新的活动
     *
     * @param  string  $ability
     * @param  callable|string  $callback
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function define($ability, $callback)
    {
        if (is_callable($callback)) {
            $this->abilities[$ability] = $callback;
        } elseif (is_string($callback)) {
            $this->stringCallbacks[$ability] = $callback;

            $this->abilities[$ability] = $this->buildAbilityCallback($ability, $callback);
        } else {
            throw new InvalidArgumentException("Callback must be a callable or a 'Class@method' string.");
        }

        return $this;
    }

    /**
     * Define abilities for a resource.
	 * 定义资源的能力
     *
     * @param  string  $name
     * @param  string  $class
     * @param  array|null  $abilities
     * @return $this
     */
    public function resource($name, $class, array $abilities = null)
    {
        $abilities = $abilities ?: [
            'viewAny' => 'viewAny',
            'view'    => 'view',
            'create'  => 'create',
            'update'  => 'update',
            'delete'  => 'delete',
        ];

        foreach ($abilities as $ability => $method) {
            $this->define($name.'.'.$ability, $class.'@'.$method);
        }

        return $this;
    }

    /**
     * Create the ability callback for a callback string.
	 * 创建能力回调为回调字符串
     *
     * @param  string  $ability
     * @param  string  $callback
     * @return \Closure
     */
    protected function buildAbilityCallback($ability, $callback)
    {
        return function () use ($ability, $callback) {
            if (Str::contains($callback, '@')) {
                [$class, $method] = Str::parseCallback($callback);
            } else {
                $class = $callback;
            }

            $policy = $this->resolvePolicy($class);

            $arguments = func_get_args();

            $user = array_shift($arguments);

            $result = $this->callPolicyBefore(
                $policy, $user, $ability, $arguments
            );

            if (! is_null($result)) {
                return $result;
            }

            return isset($method)
                    ? $policy->{$method}(...func_get_args())
                    : $policy(...func_get_args());
        };
    }

    /**
     * Define a policy class for a given class type.
	 * 定义策略类为给定的类类型
     *
     * @param  string  $class
     * @param  string  $policy
     * @return $this
     */
    public function policy($class, $policy)
    {
        $this->policies[$class] = $policy;

        return $this;
    }

    /**
     * Register a callback to run before all Gate checks.
	 * 注册一个回调，以便在所有Gate检查之前运行。
     *
     * @param  callable  $callback
     * @return $this
     */
    public function before(callable $callback)
    {
        $this->beforeCallbacks[] = $callback;

        return $this;
    }

    /**
     * Register a callback to run after all Gate checks.
	 * 注册一个回调，在所有Gate检查之后运行。
     *
     * @param  callable  $callback
     * @return $this
     */
    public function after(callable $callback)
    {
        $this->afterCallbacks[] = $callback;

        return $this;
    }

    /**
     * Determine if the given ability should be granted for the current user.
	 * 确定是否应该为当前用户授予给定的能力
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function allows($ability, $arguments = [])
    {
        return $this->check($ability, $arguments);
    }

    /**
     * Determine if the given ability should be denied for the current user.
	 * 确定当前用户是否应该拒绝给定的能力
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function denies($ability, $arguments = [])
    {
        return ! $this->allows($ability, $arguments);
    }

    /**
     * Determine if all of the given abilities should be granted for the current user.
	 * 确定是否应该为当前用户授予所有给定的能力
     *
     * @param  iterable|string  $abilities
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function check($abilities, $arguments = [])
    {
        return collect($abilities)->every(function ($ability) use ($arguments) {
            return $this->inspect($ability, $arguments)->allowed();
        });
    }

    /**
     * Determine if any one of the given abilities should be granted for the current user.
	 * 确定是否应该为当前用户授予给定的任何一种能力
     *
     * @param  iterable|string  $abilities
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function any($abilities, $arguments = [])
    {
        return collect($abilities)->contains(function ($ability) use ($arguments) {
            return $this->check($ability, $arguments);
        });
    }

    /**
     * Determine if all of the given abilities should be denied for the current user.
	 * 确定是否应该拒绝当前用户的所有给定能力
     *
     * @param  iterable|string  $abilities
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function none($abilities, $arguments = [])
    {
        return ! $this->any($abilities, $arguments);
    }

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
    public function authorize($ability, $arguments = [])
    {
        return $this->inspect($ability, $arguments)->authorize();
    }

    /**
     * Inspect the user for the given ability.
	 * 检查用户是否具有给定的能力
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return \Illuminate\Auth\Access\Response
     */
    public function inspect($ability, $arguments = [])
    {
        try {
            $result = $this->raw($ability, $arguments);

            if ($result instanceof Response) {
                return $result;
            }

            return $result ? Response::allow() : Response::deny();
        } catch (AuthorizationException $e) {
            return $e->toResponse();
        }
    }

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
    public function raw($ability, $arguments = [])
    {
        $arguments = Arr::wrap($arguments);

        $user = $this->resolveUser();

        // First we will call the "before" callbacks for the Gate. If any of these give
        // back a non-null response, we will immediately return that result in order
        // to let the developers override all checks for some authorization cases.
		// 首先，我们将调用Gate的"before"回调。如果其中任何一个返回非空响应，
		// 我们将立即返回该结果，以便让开发人员覆盖某些授权情况的所有检查。
        $result = $this->callBeforeCallbacks(
            $user, $ability, $arguments
        );

        if (is_null($result)) {
            $result = $this->callAuthCallback($user, $ability, $arguments);
        }

        // After calling the authorization callback, we will call the "after" callbacks
        // that are registered with the Gate, which allows a developer to do logging
        // if that is required for this application. Then we'll return the result.
		// 调用授权回调后，我们将调用在Gate中注册的"After"回调，
		// 这允许开发人员在应用程序需要时进行日志记录。然后我们将返回结果。
        return $this->callAfterCallbacks(
            $user, $ability, $arguments, $result
        );
    }

    /**
     * Determine whether the callback/method can be called with the given user.
	 * 确定是否可以用给定的用户调用回调/方法
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     * @param  \Closure|string|array  $class
     * @param  string|null  $method
     * @return bool
     */
    protected function canBeCalledWithUser($user, $class, $method = null)
    {
        if (! is_null($user)) {
            return true;
        }

        if (! is_null($method)) {
            return $this->methodAllowsGuests($class, $method);
        }

        if (is_array($class)) {
            $className = is_string($class[0]) ? $class[0] : get_class($class[0]);

            return $this->methodAllowsGuests($className, $class[1]);
        }

        return $this->callbackAllowsGuests($class);
    }

    /**
     * Determine if the given class method allows guests.
	 * 确定给定的类方法是否允许来宾
     *
     * @param  string  $class
     * @param  string  $method
     * @return bool
     */
    protected function methodAllowsGuests($class, $method)
    {
        try {
            $reflection = new ReflectionClass($class);

            $method = $reflection->getMethod($method);
        } catch (Exception $e) {
            return false;
        }

        if ($method) {
            $parameters = $method->getParameters();

            return isset($parameters[0]) && $this->parameterAllowsGuests($parameters[0]);
        }

        return false;
    }

    /**
     * Determine if the callback allows guests.
	 * 确定回调是否允许来宾
     *
     * @param  callable  $callback
     * @return bool
     *
     * @throws \ReflectionException
     */
    protected function callbackAllowsGuests($callback)
    {
        $parameters = (new ReflectionFunction($callback))->getParameters();

        return isset($parameters[0]) && $this->parameterAllowsGuests($parameters[0]);
    }

    /**
     * Determine if the given parameter allows guests.
	 * 确定给定参数是否允许来宾
     *
     * @param  \ReflectionParameter  $parameter
     * @return bool
     */
    protected function parameterAllowsGuests($parameter)
    {
        return ($parameter->hasType() && $parameter->allowsNull()) ||
               ($parameter->isDefaultValueAvailable() && is_null($parameter->getDefaultValue()));
    }

    /**
     * Resolve and call the appropriate authorization callback.
	 * 解析并调用适当的授权回调
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     * @param  string  $ability
     * @param  array  $arguments
     * @return bool
     */
    protected function callAuthCallback($user, $ability, array $arguments)
    {
        $callback = $this->resolveAuthCallback($user, $ability, $arguments);

        return $callback($user, ...$arguments);
    }

    /**
     * Call all of the before callbacks and return if a result is given.
	 * 调用所有的before回调函数，并在给出结果时返回。
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     * @param  string  $ability
     * @param  array  $arguments
     * @return bool|null
     */
    protected function callBeforeCallbacks($user, $ability, array $arguments)
    {
        foreach ($this->beforeCallbacks as $before) {
            if (! $this->canBeCalledWithUser($user, $before)) {
                continue;
            }

            if (! is_null($result = $before($user, $ability, $arguments))) {
                return $result;
            }
        }
    }

    /**
     * Call all of the after callbacks with check result.
	 * 调用所有带有检查结果的after回调函数
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $ability
     * @param  array  $arguments
     * @param  bool  $result
     * @return bool|null
     */
    protected function callAfterCallbacks($user, $ability, array $arguments, $result)
    {
        foreach ($this->afterCallbacks as $after) {
            if (! $this->canBeCalledWithUser($user, $after)) {
                continue;
            }

            $afterResult = $after($user, $ability, $result, $arguments);

            $result = $result ?? $afterResult;
        }

        return $result;
    }

    /**
     * Resolve the callable for the given ability and arguments.
	 * 解析给定能力和参数的可调用对象
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     * @param  string  $ability
     * @param  array  $arguments
     * @return callable
     */
    protected function resolveAuthCallback($user, $ability, array $arguments)
    {
        if (isset($arguments[0]) &&
            ! is_null($policy = $this->getPolicyFor($arguments[0])) &&
            $callback = $this->resolvePolicyCallback($user, $ability, $arguments, $policy)) {
            return $callback;
        }

        if (isset($this->stringCallbacks[$ability])) {
            [$class, $method] = Str::parseCallback($this->stringCallbacks[$ability]);

            if ($this->canBeCalledWithUser($user, $class, $method ?: '__invoke')) {
                return $this->abilities[$ability];
            }
        }

        if (isset($this->abilities[$ability]) &&
            $this->canBeCalledWithUser($user, $this->abilities[$ability])) {
            return $this->abilities[$ability];
        }

        return function () {
            //
        };
    }

    /**
     * Get a policy instance for a given class.
	 * 得到给定类的策略实例
     *
     * @param  object|string  $class
     * @return mixed
     */
    public function getPolicyFor($class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        if (! is_string($class)) {
            return;
        }

        if (isset($this->policies[$class])) {
            return $this->resolvePolicy($this->policies[$class]);
        }

        foreach ($this->guessPolicyName($class) as $guessedPolicy) {
            if (class_exists($guessedPolicy)) {
                return $this->resolvePolicy($guessedPolicy);
            }
        }

        foreach ($this->policies as $expected => $policy) {
            if (is_subclass_of($class, $expected)) {
                return $this->resolvePolicy($policy);
            }
        }
    }

    /**
     * Guess the policy name for the given class.
	 * 猜测给定类的策略名称
     *
     * @param  string  $class
     * @return array
     */
    protected function guessPolicyName($class)
    {
        if ($this->guessPolicyNamesUsingCallback) {
            return Arr::wrap(call_user_func($this->guessPolicyNamesUsingCallback, $class));
        }

        $classDirname = str_replace('/', '\\', dirname(str_replace('\\', '/', $class)));

        return [$classDirname.'\\Policies\\'.class_basename($class).'Policy'];
    }

    /**
     * Specify a callback to be used to guess policy names.
	 * 指定用于猜测策略名称的回调
     *
     * @param  callable  $callback
     * @return $this
     */
    public function guessPolicyNamesUsing(callable $callback)
    {
        $this->guessPolicyNamesUsingCallback = $callback;

        return $this;
    }

    /**
     * Build a policy class instance of the given type.
	 * 构建给定类型的策略类实例
     *
     * @param  object|string  $class
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function resolvePolicy($class)
    {
        return $this->container->make($class);
    }

    /**
     * Resolve the callback for a policy check.
	 * 解析策略检查的回调
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $ability
     * @param  array  $arguments
     * @param  mixed  $policy
     * @return bool|callable
     */
    protected function resolvePolicyCallback($user, $ability, array $arguments, $policy)
    {
        if (! is_callable([$policy, $this->formatAbilityToMethod($ability)])) {
            return false;
        }

        return function () use ($user, $ability, $arguments, $policy) {
            // This callback will be responsible for calling the policy's before method and
            // running this policy method if necessary. This is used to when objects are
            // mapped to policy objects in the user's configurations or on this class.
			// 此回调将负责调用策略的before方法，并在必要时运行此策略方法。
			// 这用于将对象映射到用户配置中或此类上的策略对象。
            $result = $this->callPolicyBefore(
                $policy, $user, $ability, $arguments
            );

            // When we receive a non-null result from this before method, we will return it
            // as the "final" results. This will allow developers to override the checks
            // in this policy to return the result for all rules defined in the class.
			// 当我们从before方法收到非空结果时，我们将返回它作为"最终"结果。
			// 这将允许开发人员覆盖此策略中的检查，以返回类中定义的所有规则的结果。
            if (! is_null($result)) {
                return $result;
            }

            $method = $this->formatAbilityToMethod($ability);

            return $this->callPolicyMethod($policy, $method, $user, $arguments);
        };
    }

    /**
     * Call the "before" method on the given policy, if applicable.
	 * 在给定策略上调用"before"方法(如果适用)
     *
     * @param  mixed  $policy
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $ability
     * @param  array  $arguments
     * @return mixed
     */
    protected function callPolicyBefore($policy, $user, $ability, $arguments)
    {
        if (! method_exists($policy, 'before')) {
            return;
        }

        if ($this->canBeCalledWithUser($user, $policy, 'before')) {
            return $policy->before($user, $ability, ...$arguments);
        }
    }

    /**
     * Call the appropriate method on the given policy.
	 * 调用适当的方法在给定策略上
     *
     * @param  mixed  $policy
     * @param  string  $method
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     * @param  array  $arguments
     * @return mixed
     */
    protected function callPolicyMethod($policy, $method, $user, array $arguments)
    {
        // If this first argument is a string, that means they are passing a class name
        // to the policy. We will remove the first argument from this argument array
        // because this policy already knows what type of models it can authorize.
		// 如果第一个参数是字符串，则意味着它们正在向策略传递类名。
		// 我们将从此参数数组中删除第一个参数，因为此策略已经知道它可以授权哪种类型的模型。
        if (isset($arguments[0]) && is_string($arguments[0])) {
            array_shift($arguments);
        }

        if (! is_callable([$policy, $method])) {
            return;
        }

        if ($this->canBeCalledWithUser($user, $policy, $method)) {
            return $policy->{$method}($user, ...$arguments);
        }
    }

    /**
     * Format the policy ability into a method name.
	 * 格式化策略功能为方法名称
     *
     * @param  string  $ability
     * @return string
     */
    protected function formatAbilityToMethod($ability)
    {
        return strpos($ability, '-') !== false ? Str::camel($ability) : $ability;
    }

    /**
     * Get a gate instance for the given user.
	 * 得到给定用户的gate实例
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|mixed  $user
     * @return static
     */
    public function forUser($user)
    {
        $callback = function () use ($user) {
            return $user;
        };

        return new static(
            $this->container, $callback, $this->abilities,
            $this->policies, $this->beforeCallbacks, $this->afterCallbacks,
            $this->guessPolicyNamesUsingCallback
        );
    }

    /**
     * Resolve the user from the user resolver.
	 * 解析用户从用户解析器中
     *
     * @return mixed
     */
    protected function resolveUser()
    {
        return call_user_func($this->userResolver);
    }

    /**
     * Get all of the defined abilities.
	 * 得到所有已定义的能力
     *
     * @return array
     */
    public function abilities()
    {
        return $this->abilities;
    }

    /**
     * Get all of the defined policies.
	 * 得到所有已定义的策略
     *
     * @return array
     */
    public function policies()
    {
        return $this->policies;
    }
}
