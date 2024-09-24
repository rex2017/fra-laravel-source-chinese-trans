<?php
/**
 * 契约，文件系统云接口
 */

namespace Illuminate\Contracts\Filesystem;

interface Cloud extends Filesystem
{
    /**
     * Get the URL for the file at the given path.
	 * 得到给定路径下文件的URL
     *
     * @param  string  $path
     * @return string
     */
    public function url($path);
}
