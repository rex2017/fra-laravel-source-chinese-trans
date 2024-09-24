<?php
/**
 * 控制台检测命名空间
 */

namespace Illuminate\Console;

use Illuminate\Container\Container;

/**
 * @deprecated Usage of this trait is deprecated and it will be removed in Laravel 7.0.
 */
trait DetectsApplicationNamespace
{
    /**
     * Get the application namespace.
	 * 得到应用程序命名空间
     *
     * @return string
     */
    protected function getAppNamespace()
    {
        return Container::getInstance()->getNamespace();
    }
}
