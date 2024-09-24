<?php
/**
 * Http，可能丢失
 */

namespace Illuminate\Http\Resources;

interface PotentiallyMissing
{
    /**
     * Determine if the object should be considered "missing".
	 * 确定该对象是否应该被视为"丢失"
     *
     * @return bool
     */
    public function isMissing();
}
