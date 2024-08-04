<?php
/**
 * 契约，JSON接口
 */

namespace Illuminate\Contracts\Support;

interface Jsonable
{
    /**
     * Convert the object to its JSON representation.
	 * 转换对象为JSON
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0);
}
