<?php
/**
 * 数据库，Eloquent隐藏属性
 */

namespace Illuminate\Database\Eloquent\Concerns;

trait HidesAttributes
{
    /**
     * The attributes that should be hidden for serialization.
	 * 应该为序列化隐藏的属性
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * The attributes that should be visible in serialization.
	 * 应该在序列化中可见的属性
     *
     * @var array
     */
    protected $visible = [];

    /**
     * Get the hidden attributes for the model.
	 * 得到模型的隐藏属性
     *
     * @return array
     */
    public function getHidden()
    {
        return $this->hidden;
    }

    /**
     * Set the hidden attributes for the model.
	 * 设置模型的隐藏属性
     *
     * @param  array  $hidden
     * @return $this
     */
    public function setHidden(array $hidden)
    {
        $this->hidden = $hidden;

        return $this;
    }

    /**
     * Add hidden attributes for the model.
	 * 添加模型隐藏属性
     *
     * @param  array|string|null  $attributes
     * @return void
     */
    public function addHidden($attributes = null)
    {
        $this->hidden = array_merge(
            $this->hidden, is_array($attributes) ? $attributes : func_get_args()
        );
    }

    /**
     * Get the visible attributes for the model.
	 * 得到模型的可见属性
     *
     * @return array
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     * Set the visible attributes for the model.
	 * 设置模型的可见属性
     *
     * @param  array  $visible
     * @return $this
     */
    public function setVisible(array $visible)
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Add visible attributes for the model.
	 * 添加模型的可见属性
     *
     * @param  array|string|null  $attributes
     * @return void
     */
    public function addVisible($attributes = null)
    {
        $this->visible = array_merge(
            $this->visible, is_array($attributes) ? $attributes : func_get_args()
        );
    }

    /**
     * Make the given, typically hidden, attributes visible.
	 * 使给定的(通常是隐藏的)属性可见
     *
     * @param  array|string  $attributes
     * @return $this
     */
    public function makeVisible($attributes)
    {
        $this->hidden = array_diff($this->hidden, (array) $attributes);

        if (! empty($this->visible)) {
            $this->addVisible($attributes);
        }

        return $this;
    }

    /**
     * Make the given, typically visible, attributes hidden.
	 * 使给定的(通常是隐藏的)属性隐藏
     *
     * @param  array|string  $attributes
     * @return $this
     */
    public function makeHidden($attributes)
    {
        $attributes = (array) $attributes;

        $this->visible = array_diff($this->visible, $attributes);

        $this->hidden = array_unique(array_merge($this->hidden, $attributes));

        return $this;
    }
}
