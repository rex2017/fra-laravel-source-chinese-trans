<?php
/**
 * 视图，文件引擎
 */

namespace Illuminate\View\Engines;

use Illuminate\Contracts\View\Engine;

class FileEngine implements Engine
{
    /**
     * Get the evaluated contents of the view.
	 * 得到视图的求值内容
     *
     * @param  string  $path
     * @param  array  $data
     * @return string
     */
    public function get($path, array $data = [])
    {
        return file_get_contents($path);
    }
}
