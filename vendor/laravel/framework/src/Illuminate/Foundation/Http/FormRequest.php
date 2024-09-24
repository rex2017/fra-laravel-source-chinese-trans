<?php
/**
 * 基础，Http表单请求
 */

namespace Illuminate\Foundation\Http;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Contracts\Validation\ValidatesWhenResolved;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Validation\ValidatesWhenResolvedTrait;
use Illuminate\Validation\ValidationException;

class FormRequest extends Request implements ValidatesWhenResolved
{
    use ValidatesWhenResolvedTrait;

    /**
     * The container instance.
	 * 容器实例
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * The redirector instance.
	 * 重定向实例
     *
     * @var \Illuminate\Routing\Redirector
     */
    protected $redirector;

    /**
     * The URI to redirect to if validation fails.
	 * 验证失败时要重定向的URI
     *
     * @var string
     */
    protected $redirect;

    /**
     * The route to redirect to if validation fails.
	 * 验证失败时要重定向的路由
     *
     * @var string
     */
    protected $redirectRoute;

    /**
     * The controller action to redirect to if validation fails.
	 * 验证失败时要重定向的控制器
     *
     * @var string
     */
    protected $redirectAction;

    /**
     * The key to be used for the view error bag.
	 * 用于视图错误包的键
     *
     * @var string
     */
    protected $errorBag = 'default';

    /**
     * The validator instance.
	 * 验证器实例
     *
     * @var \Illuminate\Contracts\Validation\Validator
     */
    protected $validator;

    /**
     * Get the validator instance for the request.
	 * 得到请求的验证器实例
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function getValidatorInstance()
    {
        if ($this->validator) {
            return $this->validator;
        }

        $factory = $this->container->make(ValidationFactory::class);

        if (method_exists($this, 'validator')) {
            $validator = $this->container->call([$this, 'validator'], compact('factory'));
        } else {
            $validator = $this->createDefaultValidator($factory);
        }

        if (method_exists($this, 'withValidator')) {
            $this->withValidator($validator);
        }

        $this->setValidator($validator);

        return $this->validator;
    }

    /**
     * Create the default validator instance.
	 * 创建默认验证器实例
     *
     * @param  \Illuminate\Contracts\Validation\Factory  $factory
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function createDefaultValidator(ValidationFactory $factory)
    {
        return $factory->make(
            $this->validationData(), $this->container->call([$this, 'rules']),
            $this->messages(), $this->attributes()
        );
    }

    /**
     * Get data to be validated from the request.
	 * 得到要验证的数据从请求中
     *
     * @return array
     */
    public function validationData()
    {
        return $this->all();
    }

    /**
     * Handle a failed validation attempt.
	 * 处理失败的验证尝试
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function failedValidation(Validator $validator)
    {
        throw (new ValidationException($validator))
                    ->errorBag($this->errorBag)
                    ->redirectTo($this->getRedirectUrl());
    }

    /**
     * Get the URL to redirect to on a validation error.
	 * 得到验证错误时要重定向到的URL
     *
     * @return string
     */
    protected function getRedirectUrl()
    {
        $url = $this->redirector->getUrlGenerator();

        if ($this->redirect) {
            return $url->to($this->redirect);
        } elseif ($this->redirectRoute) {
            return $url->route($this->redirectRoute);
        } elseif ($this->redirectAction) {
            return $url->action($this->redirectAction);
        }

        return $url->previous();
    }

    /**
     * Determine if the request passes the authorization check.
	 * 确定请求是否通过授权检查
     *
     * @return bool
     */
    protected function passesAuthorization()
    {
        if (method_exists($this, 'authorize')) {
            return $this->container->call([$this, 'authorize']);
        }

        return true;
    }

    /**
     * Handle a failed authorization attempt.
	 * 处理失败的授权尝试
     *
     * @return void
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function failedAuthorization()
    {
        throw new AuthorizationException;
    }

    /**
     * Get the validated data from the request.
	 * 得到经过验证的数据从请求中
     *
     * @return array
     */
    public function validated()
    {
        return $this->validator->validated();
    }

    /**
     * Get custom messages for validator errors.
	 * 得到验证器错误的自定义消息
     *
     * @return array
     */
    public function messages()
    {
        return [];
    }

    /**
     * Get custom attributes for validator errors.
	 * 得到验证器错误的自定义属性
     *
     * @return array
     */
    public function attributes()
    {
        return [];
    }

    /**
     * Set the Validator instance.
	 * 设置验证器实例
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return $this
     */
    public function setValidator(Validator $validator)
    {
        $this->validator = $validator;

        return $this;
    }

    /**
     * Set the Redirector instance.
	 * 设置重定向实例
     *
     * @param  \Illuminate\Routing\Redirector  $redirector
     * @return $this
     */
    public function setRedirector(Redirector $redirector)
    {
        $this->redirector = $redirector;

        return $this;
    }

    /**
     * Set the container implementation.
	 * 设置容器实现
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return $this
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;

        return $this;
    }
}
