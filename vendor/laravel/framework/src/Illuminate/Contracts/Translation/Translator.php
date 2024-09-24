<?php
/**
 * 契约，翻译接口
 */

namespace Illuminate\Contracts\Translation;

interface Translator
{
    /**
     * Get the translation for a given key.
	 * 得到给定键的翻译
     *
     * @param  string  $key
     * @param  array  $replace
     * @param  string|null  $locale
     * @return mixed
     */
    public function get($key, array $replace = [], $locale = null);

    /**
     * Get a translation according to an integer value.
	 * 得到翻译通过整数值
     *
     * @param  string  $key
     * @param  \Countable|int|array  $number
     * @param  array  $replace
     * @param  string|null  $locale
     * @return string
     */
    public function choice($key, $number, array $replace = [], $locale = null);

    /**
     * Get the default locale being used.
	 * 得到默认本地
     *
     * @return string
     */
    public function getLocale();

    /**
     * Set the default locale.
	 * 设置本地
     *
     * @param  string  $locale
     * @return void
     */
    public function setLocale($locale);
}
