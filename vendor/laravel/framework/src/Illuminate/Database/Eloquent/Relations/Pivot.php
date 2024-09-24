<?php
/**
 * 数据库，Eloquent轴心点
 */

namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Concerns\AsPivot;

class Pivot extends Model
{
    use AsPivot;

    /**
     * Indicates if the IDs are auto-incrementing.
	 * 指示ID是否自动递增
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that aren't mass assignable.
	 * 不能大规模分配的属性
     *
     * @var array
     */
    protected $guarded = [];
}
