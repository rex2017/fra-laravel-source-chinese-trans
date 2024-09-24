<?php
/**
 * 支持，可以定位的
 */

namespace Illuminate\Support\Traits;

use Illuminate\Container\Container;

trait Localizable
{
    /**
     * Run the callback with the given locale.
	 * 运行回调使用给定的区域
     *
     * @param  string  $locale
     * @param  \Closure  $callback
     * @return mixed
     */
    public function withLocale($locale, $callback)
    {
        if (! $locale) {
            return $callback();
        }

        $app = Container::getInstance();

        $original = $app->getLocale();

        try {
            $app->setLocale($locale);

            return $callback();
        } finally {
            $app->setLocale($original);
        }
    }
}
