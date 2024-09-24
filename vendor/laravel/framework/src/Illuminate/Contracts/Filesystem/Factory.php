<?php
/**
 * 契约，文件系统工厂接口
 */

namespace Illuminate\Contracts\Filesystem;

interface Factory
{
    /**
     * Get a filesystem implementation.
	 * 得到文件系统实现
     *
     * @param  string|null  $name
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    public function disk($name = null);
}
