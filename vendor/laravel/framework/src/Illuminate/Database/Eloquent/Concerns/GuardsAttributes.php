<?php
/**
 * 数据库，Eloquent守卫属性
 */

namespace Illuminate\Database\Eloquent\Concerns;

use Illuminate\Support\Str;

trait GuardsAttributes
{
    /**
     * The attributes that are mass assignable.
	 * 可大量分配的属性
     *
     * @var array
     */
    protected $fillable = [];

    /**
     * The attributes that aren't mass assignable.
	 * 不能大规模分配的属性
     *
     * @var array
     */
    protected $guarded = ['*'];

    /**
     * Indicates if all mass assignment is enabled.
	 * 指明是否启用了所有的质量分配
     *
     * @var bool
     */
    protected static $unguarded = false;

    /**
     * The actual columns that exist on the database and can be guarded.
	 * 存在于数据库中并且可以被保护的实际列
     *
     * @var array
     */
    protected static $guardableColumns = [];

    /**
     * Get the fillable attributes for the model.
	 * 得到模型的可填充属性
     *
     * @return array
     */
    public function getFillable()
    {
        return $this->fillable;
    }

    /**
     * Set the fillable attributes for the model.
	 * 设置可填充属性
     *
     * @param  array  $fillable
     * @return $this
     */
    public function fillable(array $fillable)
    {
        $this->fillable = $fillable;

        return $this;
    }

    /**
     * Get the guarded attributes for the model.
	 * 得到模型的保护属性
     *
     * @return array
     */
    public function getGuarded()
    {
        return $this->guarded;
    }

    /**
     * Set the guarded attributes for the model.
	 * 设置受保护的属性为模型
     *
     * @param  array  $guarded
     * @return $this
     */
    public function guard(array $guarded)
    {
        $this->guarded = $guarded;

        return $this;
    }

    /**
     * Disable all mass assignable restrictions.
	 * 禁用所有可批量分配的限制
     *
     * @param  bool  $state
     * @return void
     */
    public static function unguard($state = true)
    {
        static::$unguarded = $state;
    }

    /**
     * Enable the mass assignment restrictions.
	 * 启用质量分配限制
     *
     * @return void
     */
    public static function reguard()
    {
        static::$unguarded = false;
    }

    /**
     * Determine if current state is "unguarded".
	 * 确定当前状态是否为"未保护"
     *
     * @return bool
     */
    public static function isUnguarded()
    {
        return static::$unguarded;
    }

    /**
     * Run the given callable while being unguarded.
	 * 运行给定的可调用对象在不受保护的情况下
     *
     * @param  callable  $callback
     * @return mixed
     */
    public static function unguarded(callable $callback)
    {
        if (static::$unguarded) {
            return $callback();
        }

        static::unguard();

        try {
            return $callback();
        } finally {
            static::reguard();
        }
    }

    /**
     * Determine if the given attribute may be mass assigned.
	 * 确定给定的属性是否可以被批量分配
     *
     * @param  string  $key
     * @return bool
     */
    public function isFillable($key)
    {
        if (static::$unguarded) {
            return true;
        }

        // If the key is in the "fillable" array, we can of course assume that it's
        // a fillable attribute. Otherwise, we will check the guarded array when
        // we need to determine if the attribute is black-listed on the model.
		// 如果键在"可填充"数组中，我们当然可以假设它是可填写的属性。
		// 否则，我们将在以下情况下检查爱保护的阵列，
		// 我们需要确定该属性是否在模型上被列入黑名单。
        if (in_array($key, $this->getFillable())) {
            return true;
        }

        // If the attribute is explicitly listed in the "guarded" array then we can
        // return false immediately. This means this attribute is definitely not
        // fillable and there is no point in going any further in this method.
		// 如果该属性明确列在"受保护"数组中，那么我们可以立即返回false。
		// 这意味着此属性绝对不是可填充，在这种方法中继续下去没有意义。
        if ($this->isGuarded($key)) {
            return false;
        }

        return empty($this->getFillable()) &&
            strpos($key, '.') === false &&
            ! Str::startsWith($key, '_');
    }

    /**
     * Determine if the given key is guarded.
	 * 确定给定的密钥是否受到保护
     *
     * @param  string  $key
     * @return bool
     */
    public function isGuarded($key)
    {
        if (empty($this->getGuarded())) {
            return false;
        }

        return $this->getGuarded() == ['*'] ||
               ! empty(preg_grep('/^'.preg_quote($key).'$/i', $this->getGuarded())) ||
               ! $this->isGuardableColumn($key);
    }

    /**
     * Determine if the given column is a valid, guardable column.
	 * 确定给定的列是否是有效的、可保护的列
     *
     * @param  string  $key
     * @return bool
     */
    protected function isGuardableColumn($key)
    {
        if (! isset(static::$guardableColumns[get_class($this)])) {
            static::$guardableColumns[get_class($this)] = $this->getConnection()
                        ->getSchemaBuilder()
                        ->getColumnListing($this->getTable());
        }

        return in_array($key, static::$guardableColumns[get_class($this)]);
    }

    /**
     * Determine if the model is totally guarded.
	 * 确定模型是否被完全保护
     *
     * @return bool
     */
    public function totallyGuarded()
    {
        return count($this->getFillable()) === 0 && $this->getGuarded() == ['*'];
    }

    /**
     * Get the fillable attributes of a given array.
	 * 得到给定数组的可填充属性
     *
     * @param  array  $attributes
     * @return array
     */
    protected function fillableFromArray(array $attributes)
    {
        if (count($this->getFillable()) > 0 && ! static::$unguarded) {
            return array_intersect_key($attributes, array_flip($this->getFillable()));
        }

        return $attributes;
    }
}
