<?php
/**
 * 契约，文件云接口
 */

namespace Illuminate\Contracts\Filesystem;

interface Cloud extends Filesystem
{
    /**
     * Get the URL for the file at the given path.
	 * 得到文件链接
     *
     * @param  string  $path
     * @return string
     */
    public function url($path);
}
