<?php
/**
 * Http，与闪存数据交互
 */

namespace Illuminate\Http\Concerns;

trait InteractsWithFlashData
{
    /**
     * Retrieve an old input item.
	 * 检索旧的输入项
     *
     * @param  string|null  $key
     * @param  string|array|null  $default
     * @return string|array
     */
    public function old($key = null, $default = null)
    {
        return $this->hasSession() ? $this->session()->getOldInput($key, $default) : $default;
    }

    /**
     * Flash the input for the current request to the session.
	 * 闪存当前请求的输入到会话中
     *
     * @return void
     */
    public function flash()
    {
        $this->session()->flashInput($this->input());
    }

    /**
     * Flash only some of the input to the session.
	 * 只将部分输入Flash到会话中
     *
     * @param  array|mixed  $keys
     * @return void
     */
    public function flashOnly($keys)
    {
        $this->session()->flashInput(
            $this->only(is_array($keys) ? $keys : func_get_args())
        );
    }

    /**
     * Flash only some of the input to the session.
	 * 只将部分输入Flash到会话中
     *
     * @param  array|mixed  $keys
     * @return void
     */
    public function flashExcept($keys)
    {
        $this->session()->flashInput(
            $this->except(is_array($keys) ? $keys : func_get_args())
        );
    }

    /**
     * Flush all of the old input from the session.
	 * 清除会话中的所有旧输入
     *
     * @return void
     */
    public function flush()
    {
        $this->session()->flashInput([]);
    }
}
