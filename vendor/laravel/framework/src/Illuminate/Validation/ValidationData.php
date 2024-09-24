<?php
/**
 * 验证数据
 */

namespace Illuminate\Validation;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ValidationData
{
    /**
     * Initialize and gather data for given attribute.
	 * 初始化并收集给定属性的数据
     *
     * @param  string  $attribute
     * @param  array  $masterData
     * @return array
     */
    public static function initializeAndGatherData($attribute, $masterData)
    {
        $data = Arr::dot(static::initializeAttributeOnData($attribute, $masterData));

        return array_merge($data, static::extractValuesForWildcards(
            $masterData, $data, $attribute
        ));
    }

    /**
     * Gather a copy of the attribute data filled with any missing attributes.
	 * 收集属性数据的副本，其中填充任何缺失的属性。
     *
     * @param  string  $attribute
     * @param  array  $masterData
     * @return array
     */
    protected static function initializeAttributeOnData($attribute, $masterData)
    {
        $explicitPath = static::getLeadingExplicitAttributePath($attribute);

        $data = static::extractDataFromPath($explicitPath, $masterData);

        if (! Str::contains($attribute, '*') || Str::endsWith($attribute, '*')) {
            return $data;
        }

        return data_set($data, $attribute, null, true);
    }

    /**
     * Get all of the exact attribute values for a given wildcard attribute.
	 * 得到给定通配符属性的所有确切属性值
     *
     * @param  array  $masterData
     * @param  array  $data
     * @param  string  $attribute
     * @return array
     */
    protected static function extractValuesForWildcards($masterData, $data, $attribute)
    {
        $keys = [];

        $pattern = str_replace('\*', '[^\.]+', preg_quote($attribute));

        foreach ($data as $key => $value) {
            if ((bool) preg_match('/^'.$pattern.'/', $key, $matches)) {
                $keys[] = $matches[0];
            }
        }

        $keys = array_unique($keys);

        $data = [];

        foreach ($keys as $key) {
            $data[$key] = Arr::get($masterData, $key);
        }

        return $data;
    }

    /**
     * Extract data based on the given dot-notated path.
	 * 提取数据根据给定的点标记路径
     *
     * Used to extract a sub-section of the data for faster iteration.
     *
     * @param  string  $attribute
     * @param  array  $masterData
     * @return array
     */
    public static function extractDataFromPath($attribute, $masterData)
    {
        $results = [];

        $value = Arr::get($masterData, $attribute, '__missing__');

        if ($value !== '__missing__') {
            Arr::set($results, $attribute, $value);
        }

        return $results;
    }

    /**
     * Get the explicit part of the attribute name.
	 * 得到属性名称的显式部分
     *
     * E.g. 'foo.bar.*.baz' -> 'foo.bar'
     *
     * Allows us to not spin through all of the flattened data for some operations.
     *
     * @param  string  $attribute
     * @return string
     */
    public static function getLeadingExplicitAttributePath($attribute)
    {
        return rtrim(explode('*', $attribute)[0], '.') ?: null;
    }
}
