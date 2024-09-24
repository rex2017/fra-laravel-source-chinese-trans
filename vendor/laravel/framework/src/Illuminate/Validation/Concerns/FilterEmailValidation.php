<?php
/**
 * 验证，过滤邮件验证
 */

namespace Illuminate\Validation\Concerns;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Validation\EmailValidation;

class FilterEmailValidation implements EmailValidation
{
    /**
     * Returns true if the given email is valid.
	 * 返回true如果给定的电子邮件有效
     *
     * @param  string  $email
     * @param  \Egulias\EmailValidator\EmailLexer  $emailLexer
     * @return bool
     */
    public function isValid($email, EmailLexer $emailLexer)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Returns the validation error.
	 * 返回验证错误
     *
     * @return \Egulias\EmailValidator\Exception\InvalidEmail|null
     */
    public function getError()
    {
        //
    }

    /**
     * Returns the validation warnings.
	 * 返回验证警告
     *
     * @return \Egulias\EmailValidator\Warning\Warning[]
     */
    public function getWarnings()
    {
        return [];
    }
}
