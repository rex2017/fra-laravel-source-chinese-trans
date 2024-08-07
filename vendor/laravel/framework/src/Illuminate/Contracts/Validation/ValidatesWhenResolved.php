<?php
/**
 * 契约，验证接口
 */

namespace Illuminate\Contracts\Validation;

interface ValidatesWhenResolved
{
    /**
     * Validate the given class instance.
	 * 验证类实例
     *
     * @return void
     */
    public function validateResolved();
}
