<?php
/**
 * 基础，可授权的
 */

namespace Illuminate\Foundation\Auth\Access;

use Illuminate\Contracts\Auth\Access\Gate;

trait Authorizable
{
    /**
     * Determine if the entity has the given abilities.
	 * 确定实体是否具有给定的能力
     *
     * @param  iterable|string  $abilities
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function can($abilities, $arguments = [])
    {
        return app(Gate::class)->forUser($this)->check($abilities, $arguments);
    }

    /**
     * Determine if the entity does not have the given abilities.
	 * 确定实体是否没有给定的能力
     *
     * @param  iterable|string  $abilities
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function cant($abilities, $arguments = [])
    {
        return ! $this->can($abilities, $arguments);
    }

    /**
     * Determine if the entity does not have the given abilities.
	 * 确定实体是否没有给定的能力
     *
     * @param  iterable|string  $abilities
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function cannot($abilities, $arguments = [])
    {
        return $this->cant($abilities, $arguments);
    }
}
