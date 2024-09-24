<?php
/**
 * 验证工厂
 */

namespace Illuminate\Validation;

use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Validation\Factory as FactoryContract;
use Illuminate\Support\Str;

class Factory implements FactoryContract
{
    /**
     * The Translator implementation.
	 * 翻译实现
     *
     * @var \Illuminate\Contracts\Translation\Translator
     */
    protected $translator;

    /**
     * The Presence Verifier implementation.
	 * 状态验证实现
     *
     * @var \Illuminate\Validation\PresenceVerifierInterface
     */
    protected $verifier;

    /**
     * The IoC container instance.
	 * 容器实例
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * All of the custom validator extensions.
	 * 所有自定义验证扩展
     *
     * @var array
     */
    protected $extensions = [];

    /**
     * All of the custom implicit validator extensions.
	 * 所有自定义隐式验证器扩展
     *
     * @var array
     */
    protected $implicitExtensions = [];

    /**
     * All of the custom dependent validator extensions.
	 * 所有自定义依赖验证器扩展
     *
     * @var array
     */
    protected $dependentExtensions = [];

    /**
     * All of the custom validator message replacers.
	 * 所有自定义消息转换
     *
     * @var array
     */
    protected $replacers = [];

    /**
     * All of the fallback messages for custom rules.
	 * 自定义规则的所有回退消息
     *
     * @var array
     */
    protected $fallbackMessages = [];

    /**
     * The Validator resolver instance.
	 * 验证器解析器实例
     *
     * @var \Closure
     */
    protected $resolver;

    /**
     * Create a new Validator factory instance.
	 * 创建新的验证工厂实例
     *
     * @param  \Illuminate\Contracts\Translation\Translator  $translator
     * @param  \Illuminate\Contracts\Container\Container|null  $container
     * @return void
     */
    public function __construct(Translator $translator, Container $container = null)
    {
        $this->container = $container;
        $this->translator = $translator;
    }

    /**
     * Create a new Validator instance.
	 * 创建新的验证实例
     *
     * @param  array  $data
     * @param  array  $rules
     * @param  array  $messages
     * @param  array  $customAttributes
     * @return \Illuminate\Validation\Validator
     */
    public function make(array $data, array $rules, array $messages = [], array $customAttributes = [])
    {
        $validator = $this->resolve(
            $data, $rules, $messages, $customAttributes
        );

        // The presence verifier is responsible for checking the unique and exists data
        // for the validator. It is behind an interface so that multiple versions of
        // it may be written besides database. We'll inject it into the validator.
		// 存在验证器负责检查验证器的唯一和存在数据。
		// 它位于接口后面，因此除了数据库外，还可以编写多个版本。我们将把它注入验证器。
        if (! is_null($this->verifier)) {
            $validator->setPresenceVerifier($this->verifier);
        }

        // Next we'll set the IoC container instance of the validator, which is used to
        // resolve out class based validator extensions. If it is not set then these
        // types of extensions will not be possible on these validation instances.
		// 接下来，我们将设置验证器的IoC容器实例，该实例用于解析出基于类的验证器扩展。
		// 如果未设置，则这些类型的扩展将无法在这些验证实例上使用。
        if (! is_null($this->container)) {
            $validator->setContainer($this->container);
        }

        $this->addExtensions($validator);

        return $validator;
    }

    /**
     * Validate the given data against the provided rules.
	 * 验证给定的数据根据提供的规则
     *
     * @param  array  $data
     * @param  array  $rules
     * @param  array  $messages
     * @param  array  $customAttributes
     * @return array
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validate(array $data, array $rules, array $messages = [], array $customAttributes = [])
    {
        return $this->make($data, $rules, $messages, $customAttributes)->validate();
    }

    /**
     * Resolve a new Validator instance.
	 * 解析新的验证器实例
     *
     * @param  array  $data
     * @param  array  $rules
     * @param  array  $messages
     * @param  array  $customAttributes
     * @return \Illuminate\Validation\Validator
     */
    protected function resolve(array $data, array $rules, array $messages, array $customAttributes)
    {
        if (is_null($this->resolver)) {
            return new Validator($this->translator, $data, $rules, $messages, $customAttributes);
        }

        return call_user_func($this->resolver, $this->translator, $data, $rules, $messages, $customAttributes);
    }

    /**
     * Add the extensions to a validator instance.
	 * 添加扩展到验证器实例
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    protected function addExtensions(Validator $validator)
    {
        $validator->addExtensions($this->extensions);

        // Next, we will add the implicit extensions, which are similar to the required
        // and accepted rule in that they are run even if the attributes is not in a
        // array of data that is given to a validator instances via instantiation.
		// 接下来，我们将添加隐式扩展，这与所需和接受的规则类似，
		// 因为即使属性不在通过实例化提供给验证器实例的数据数组中，它们也会运行。
        $validator->addImplicitExtensions($this->implicitExtensions);

        $validator->addDependentExtensions($this->dependentExtensions);

        $validator->addReplacers($this->replacers);

        $validator->setFallbackMessages($this->fallbackMessages);
    }

    /**
     * Register a custom validator extension.
	 * 注册一个自定义验证器扩展
     *
     * @param  string  $rule
     * @param  \Closure|string  $extension
     * @param  string|null  $message
     * @return void
     */
    public function extend($rule, $extension, $message = null)
    {
        $this->extensions[$rule] = $extension;

        if ($message) {
            $this->fallbackMessages[Str::snake($rule)] = $message;
        }
    }

    /**
     * Register a custom implicit validator extension.
	 * 注册自定义隐式验证器扩展
     *
     * @param  string  $rule
     * @param  \Closure|string  $extension
     * @param  string|null  $message
     * @return void
     */
    public function extendImplicit($rule, $extension, $message = null)
    {
        $this->implicitExtensions[$rule] = $extension;

        if ($message) {
            $this->fallbackMessages[Str::snake($rule)] = $message;
        }
    }

    /**
     * Register a custom dependent validator extension.
	 * 注册自定义依赖验证器扩展
     *
     * @param  string  $rule
     * @param  \Closure|string  $extension
     * @param  string|null  $message
     * @return void
     */
    public function extendDependent($rule, $extension, $message = null)
    {
        $this->dependentExtensions[$rule] = $extension;

        if ($message) {
            $this->fallbackMessages[Str::snake($rule)] = $message;
        }
    }

    /**
     * Register a custom validator message replacer.
	 * 注册一个自定义验证器消息替换程序
     *
     * @param  string  $rule
     * @param  \Closure|string  $replacer
     * @return void
     */
    public function replacer($rule, $replacer)
    {
        $this->replacers[$rule] = $replacer;
    }

    /**
     * Set the Validator instance resolver.
	 * 设置Validator实例解析器
     *
     * @param  \Closure  $resolver
     * @return void
     */
    public function resolver(Closure $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Get the Translator implementation.
	 * 得到Translator实现
     *
     * @return \Illuminate\Contracts\Translation\Translator
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * Get the Presence Verifier implementation.
	 * 得到Presence Verifier实现
     *
     * @return \Illuminate\Validation\PresenceVerifierInterface
     */
    public function getPresenceVerifier()
    {
        return $this->verifier;
    }

    /**
     * Set the Presence Verifier implementation.
	 * 设置状态验证器实现
     *
     * @param  \Illuminate\Validation\PresenceVerifierInterface  $presenceVerifier
     * @return void
     */
    public function setPresenceVerifier(PresenceVerifierInterface $presenceVerifier)
    {
        $this->verifier = $presenceVerifier;
    }
}
