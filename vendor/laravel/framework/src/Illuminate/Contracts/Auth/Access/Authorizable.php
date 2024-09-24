<?php
/**
 * 契约，可授权的
 */

namespace Illuminate\Contracts\Auth\Access;

interface Authorizable
{
    /**
     * Determine if the entity has a given ability.
	 * 确定实体是否具有给定的能力
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function can($ability, $arguments = []);
}
