<?php
/**
 * 验证已解决特性
 */

namespace Illuminate\Validation;

/**
 * Provides default implementation of ValidatesWhenResolved contract.
 * 提供ValidatesWhenResolved合约的默认实现
 */
trait ValidatesWhenResolvedTrait
{
    /**
     * Validate the class instance.
	 * 验证类实例
     *
     * @return void
     */
    public function validateResolved()
    {
        $this->prepareForValidation();

        if (! $this->passesAuthorization()) {
            $this->failedAuthorization();
        }

        $instance = $this->getValidatorInstance();

        if ($instance->fails()) {
            $this->failedValidation($instance);
        }

        $this->passedValidation();
    }

    /**
     * Prepare the data for validation.
	 * 准备验证数据
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        //
    }

    /**
     * Get the validator instance for the request.
	 * 得到请求的验证器实例
     *
     * @return \Illuminate\Validation\Validator
     */
    protected function getValidatorInstance()
    {
        return $this->validator();
    }

    /**
     * Handle a passed validation attempt.
	 * 处理通过的验证尝试
     *
     * @return void
     */
    protected function passedValidation()
    {
        //
    }

    /**
     * Handle a failed validation attempt.
	 * 处理失败的验证尝试
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new ValidationException($validator);
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
            return $this->authorize();
        }

        return true;
    }

    /**
     * Handle a failed authorization attempt.
	 * 处理失败的授权尝试
     *
     * @return void
     *
     * @throws \Illuminate\Validation\UnauthorizedException
     */
    protected function failedAuthorization()
    {
        throw new UnauthorizedException;
    }
}
