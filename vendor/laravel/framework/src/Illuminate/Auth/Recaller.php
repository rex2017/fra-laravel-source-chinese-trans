<?php
/**
 * 重调用器
 */

namespace Illuminate\Auth;

use Illuminate\Support\Str;

class Recaller
{
    /**
     * The "recaller" / "remember me" cookie string.
	 * "重调用"/"记住我"cookie字符串
     *
     * @var string
     */
    protected $recaller;

    /**
     * Create a new recaller instance.
	 * 创建新的调用器实例
     *
     * @param  string  $recaller
     * @return void
     */
    public function __construct($recaller)
    {
        $this->recaller = @unserialize($recaller, ['allowed_classes' => false]) ?: $recaller;
    }

    /**
     * Get the user ID from the recaller.
	 * 得到用户ID从调用器中
     *
     * @return string
     */
    public function id()
    {
        return explode('|', $this->recaller, 3)[0];
    }

    /**
     * Get the "remember token" token from the recaller.
	 * 得到"记住令牌"令牌从调用器
     *
     * @return string
     */
    public function token()
    {
        return explode('|', $this->recaller, 3)[1];
    }

    /**
     * Get the password from the recaller.
	 * 得到密码从召回器中
     *
     * @return string
     */
    public function hash()
    {
        return explode('|', $this->recaller, 3)[2];
    }

    /**
     * Determine if the recaller is valid.
	 * 确定调用器是否有效
     *
     * @return bool
     */
    public function valid()
    {
        return $this->properString() && $this->hasAllSegments();
    }

    /**
     * Determine if the recaller is an invalid string.
	 * 确定调用器是否有效
     *
     * @return bool
     */
    protected function properString()
    {
        return is_string($this->recaller) && Str::contains($this->recaller, '|');
    }

    /**
     * Determine if the recaller has all segments.
	 * 确定召回器是否具有所有段
     *
     * @return bool
     */
    protected function hasAllSegments()
    {
        $segments = explode('|', $this->recaller);

        return count($segments) === 3 && trim($segments[0]) !== '' && trim($segments[1]) !== '';
    }
}
