<?php
/**
 * 数据库，Eloquent有时间戳
 */

namespace Illuminate\Database\Eloquent\Concerns;

use Illuminate\Support\Facades\Date;

trait HasTimestamps
{
    /**
     * Indicates if the model should be timestamped.
	 * 指明是否应该对模型进行时间戳
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Update the model's update timestamp.
	 * 更新模型更新时间戳
     *
     * @return bool
     */
    public function touch()
    {
        if (! $this->usesTimestamps()) {
            return false;
        }

        $this->updateTimestamps();

        return $this->save();
    }

    /**
     * Update the creation and update timestamps.
	 * 更新创建和更新时间戳
     *
     * @return void
     */
    protected function updateTimestamps()
    {
        $time = $this->freshTimestamp();

        $updatedAtColumn = $this->getUpdatedAtColumn();

        if (! is_null($updatedAtColumn) && ! $this->isDirty($updatedAtColumn)) {
            $this->setUpdatedAt($time);
        }

        $createdAtColumn = $this->getCreatedAtColumn();

        if (! $this->exists && ! is_null($createdAtColumn) && ! $this->isDirty($createdAtColumn)) {
            $this->setCreatedAt($time);
        }
    }

    /**
     * Set the value of the "created at" attribute.
	 * 设置"created at"属性值
     *
     * @param  mixed  $value
     * @return $this
     */
    public function setCreatedAt($value)
    {
        $this->{$this->getCreatedAtColumn()} = $value;

        return $this;
    }

    /**
     * Set the value of the "updated at" attribute.
	 * 设置"updated at"属性值
     *
     * @param  mixed  $value
     * @return $this
     */
    public function setUpdatedAt($value)
    {
        $this->{$this->getUpdatedAtColumn()} = $value;

        return $this;
    }

    /**
     * Get a fresh timestamp for the model.
	 * 得到一个新的时间戳为模型
     *
     * @return \Illuminate\Support\Carbon
     */
    public function freshTimestamp()
    {
        return Date::now();
    }

    /**
     * Get a fresh timestamp for the model.
	 * 得到一个新的时间戳为模型
     *
     * @return string
     */
    public function freshTimestampString()
    {
        return $this->fromDateTime($this->freshTimestamp());
    }

    /**
     * Determine if the model uses timestamps.
	 * 确定模型是否使用时间戳
     *
     * @return bool
     */
    public function usesTimestamps()
    {
        return $this->timestamps;
    }

    /**
     * Get the name of the "created at" column.
	 * 得到"updated at"列的名称
     *
     * @return string|null
     */
    public function getCreatedAtColumn()
    {
        return static::CREATED_AT;
    }

    /**
     * Get the name of the "updated at" column.
	 * 得到"updated at"列的名称
     *
     * @return string|null
     */
    public function getUpdatedAtColumn()
    {
        return static::UPDATED_AT;
    }

    /**
     * Get the fully qualified "created at" column.
	 * 得到完全限定的"created at"列
     *
     * @return string
     */
    public function getQualifiedCreatedAtColumn()
    {
        return $this->qualifyColumn($this->getCreatedAtColumn());
    }

    /**
     * Get the fully qualified "updated at" column.
	 * 得到完全限定的"updated at"列
     *
     * @return string
     */
    public function getQualifiedUpdatedAtColumn()
    {
        return $this->qualifyColumn($this->getUpdatedAtColumn());
    }
}
