<?php
/**
 * Http，有条件加载属性
 */

namespace Illuminate\Http\Resources;

use Illuminate\Support\Arr;

trait ConditionallyLoadsAttributes
{
    /**
     * Filter the given data, removing any optional values.
	 * 过滤给定数据
     *
     * @param  array  $data
     * @return array
     */
    protected function filter($data)
    {
        $index = -1;

        foreach ($data as $key => $value) {
            $index++;

            if (is_array($value)) {
                $data[$key] = $this->filter($value);

                continue;
            }

            if (is_numeric($key) && $value instanceof MergeValue) {
                return $this->mergeData(
                    $data, $index, $this->filter($value->data),
                    array_values($value->data) === $value->data
                );
            }

            if ($value instanceof self && is_null($value->resource)) {
                $data[$key] = null;
            }
        }

        return $this->removeMissingValues($data);
    }

    /**
     * Merge the given data in at the given index.
	 * 合并给定数据
     *
     * @param  array  $data
     * @param  int  $index
     * @param  array  $merge
     * @param  bool  $numericKeys
     * @return array
     */
    protected function mergeData($data, $index, $merge, $numericKeys)
    {
        if ($numericKeys) {
            return $this->removeMissingValues(array_merge(
                array_merge(array_slice($data, 0, $index, true), $merge),
                $this->filter(array_values(array_slice($data, $index + 1, null, true)))
            ));
        }

        return $this->removeMissingValues(array_slice($data, 0, $index, true) +
                $merge +
                $this->filter(array_slice($data, $index + 1, null, true)));
    }

    /**
     * Remove the missing values from the filtered data.
	 * 移除丢失值
     *
     * @param  array  $data
     * @return array
     */
    protected function removeMissingValues($data)
    {
        $numericKeys = true;

        foreach ($data as $key => $value) {
            if (($value instanceof PotentiallyMissing && $value->isMissing()) ||
                ($value instanceof self &&
                $value->resource instanceof PotentiallyMissing &&
                $value->isMissing())) {
                unset($data[$key]);
            } else {
                $numericKeys = $numericKeys && is_numeric($key);
            }
        }

        if (property_exists($this, 'preserveKeys') && $this->preserveKeys === true) {
            return $data;
        }

        return $numericKeys ? array_values($data) : $data;
    }

    /**
     * Retrieve a value based on a given condition.
	 * 检索值根据给定条件
     *
     * @param  bool  $condition
     * @param  mixed  $value
     * @param  mixed  $default
     * @return \Illuminate\Http\Resources\MissingValue|mixed
     */
    protected function when($condition, $value, $default = null)
    {
        if ($condition) {
            return value($value);
        }

        return func_num_args() === 3 ? value($default) : new MissingValue;
    }

    /**
     * Merge a value into the array.
	 * 合并一个值至数组
     *
     * @param  mixed  $value
     * @return \Illuminate\Http\Resources\MergeValue|mixed
     */
    protected function merge($value)
    {
        return $this->mergeWhen(true, $value);
    }

    /**
     * Merge a value based on a given condition.
	 * 合并一个值根据给定条件
     *
     * @param  bool  $condition
     * @param  mixed  $value
     * @return \Illuminate\Http\Resources\MergeValue|mixed
     */
    protected function mergeWhen($condition, $value)
    {
        return $condition ? new MergeValue(value($value)) : new MissingValue;
    }

    /**
     * Merge the given attributes.
	 * 合并给定的属性
     *
     * @param  array  $attributes
     * @return \Illuminate\Http\Resources\MergeValue
     */
    protected function attributes($attributes)
    {
        return new MergeValue(
            Arr::only($this->resource->toArray(), $attributes)
        );
    }

    /**
     * Retrieve a relationship if it has been loaded.
	 * 检索已加载的关系
     *
     * @param  string  $relationship
     * @param  mixed  $value
     * @param  mixed  $default
     * @return \Illuminate\Http\Resources\MissingValue|mixed
     */
    protected function whenLoaded($relationship, $value = null, $default = null)
    {
        if (func_num_args() < 3) {
            $default = new MissingValue;
        }

        if (! $this->resource->relationLoaded($relationship)) {
            return value($default);
        }

        if (func_num_args() === 1) {
            return $this->resource->{$relationship};
        }

        if ($this->resource->{$relationship} === null) {
            return;
        }

        return value($value);
    }

    /**
     * Execute a callback if the given pivot table has been loaded.
	 * 执行回调，如果已加载给定的数据透视表。
     *
     * @param  string  $table
     * @param  mixed  $value
     * @param  mixed  $default
     * @return \Illuminate\Http\Resources\MissingValue|mixed
     */
    protected function whenPivotLoaded($table, $value, $default = null)
    {
        return $this->whenPivotLoadedAs('pivot', ...func_get_args());
    }

    /**
     * Execute a callback if the given pivot table with a custom accessor has been loaded.
	 * 执行回调，如果加载了带有自定义访问器的数据透视表。
     *
     * @param  string  $accessor
     * @param  string  $table
     * @param  mixed  $value
     * @param  mixed  $default
     * @return \Illuminate\Http\Resources\MissingValue|mixed
     */
    protected function whenPivotLoadedAs($accessor, $table, $value, $default = null)
    {
        if (func_num_args() === 3) {
            $default = new MissingValue;
        }

        return $this->when(
            $this->resource->$accessor &&
            ($this->resource->$accessor instanceof $table ||
            $this->resource->$accessor->getTable() === $table),
            ...[$value, $default]
        );
    }

    /**
     * Transform the given value if it is present.
	 * 如果给定值存在，则对其进行转换
     *
     * @param  mixed  $value
     * @param  callable  $callback
     * @param  mixed  $default
     * @return mixed
     */
    protected function transform($value, callable $callback, $default = null)
    {
        return transform(
            $value, $callback, func_num_args() === 3 ? $default : new MissingValue
        );
    }
}
