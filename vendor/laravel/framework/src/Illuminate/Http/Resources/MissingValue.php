<?php
/**
 * Http，丢失值
 */

namespace Illuminate\Http\Resources;

class MissingValue implements PotentiallyMissing
{
    /**
     * Determine if the object should be considered "missing".
	 * 确定是否对象将要丢失
     *
     * @return bool
     */
    public function isMissing()
    {
        return true;
    }
}
