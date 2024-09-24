<?php
/**
 * 数据库，Eloquent范围接口
 */

namespace Illuminate\Database\Eloquent;

interface Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
	 * 将范围应用于给定的Eloquent查询生成器
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model);
}
