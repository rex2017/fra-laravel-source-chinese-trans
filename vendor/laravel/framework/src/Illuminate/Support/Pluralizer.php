<?php
/**
 * 支持，复数器
 */

namespace Illuminate\Support;

use Doctrine\Inflector\CachedWordInflector;
use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\Rules\English;
use Doctrine\Inflector\RulesetInflector;

class Pluralizer
{
    /**
     * Uncountable word forms.
	 * 不可数的单词形式
     *
     * @var array
     */
    public static $uncountable = [
        'audio',
        'bison',
        'cattle',
        'chassis',
        'compensation',
        'coreopsis',
        'data',
        'deer',
        'education',
        'emoji',
        'equipment',
        'evidence',
        'feedback',
        'firmware',
        'fish',
        'furniture',
        'gold',
        'hardware',
        'information',
        'jedi',
        'kin',
        'knowledge',
        'love',
        'metadata',
        'money',
        'moose',
        'news',
        'nutrition',
        'offspring',
        'plankton',
        'pokemon',
        'police',
        'rain',
        'recommended',
        'related',
        'rice',
        'series',
        'sheep',
        'software',
        'species',
        'swine',
        'traffic',
        'wheat',
    ];

    /**
     * Get the plural form of an English word.
	 * 得到英语单词的复数形式
     *
     * @param  string  $value
     * @param  int  $count
     * @return string
     */
    public static function plural($value, $count = 2)
    {
        if ((int) abs($count) === 1 || static::uncountable($value)) {
            return $value;
        }

        $plural = static::inflector()->pluralize($value);

        return static::matchCase($plural, $value);
    }

    /**
     * Get the singular form of an English word.
	 * 得到英语单词的单数形式
     *
     * @param  string  $value
     * @return string
     */
    public static function singular($value)
    {
        $singular = static::inflector()->singularize($value);

        return static::matchCase($singular, $value);
    }

    /**
     * Determine if the given value is uncountable.
	 * 确定给定的值是否不可数
     *
     * @param  string  $value
     * @return bool
     */
    protected static function uncountable($value)
    {
        return in_array(strtolower($value), static::$uncountable);
    }

    /**
     * Attempt to match the case on two strings.
	 * 尝试在两个字符串上匹配大小写
     *
     * @param  string  $value
     * @param  string  $comparison
     * @return string
     */
    protected static function matchCase($value, $comparison)
    {
        $functions = ['mb_strtolower', 'mb_strtoupper', 'ucfirst', 'ucwords'];

        foreach ($functions as $function) {
            if ($function($comparison) === $comparison) {
                return $function($value);
            }
        }

        return $value;
    }

    /**
     * Get the inflector instance.
	 * 得到影响因子实例
     *
     * @return \Doctrine\Inflector\Inflector
     */
    public static function inflector()
    {
        static $inflector;

        if (is_null($inflector)) {
            $inflector = new Inflector(
                new CachedWordInflector(new RulesetInflector(
                    English\Rules::getSingularRuleset()
                )),
                new CachedWordInflector(new RulesetInflector(
                    English\Rules::getPluralRuleset()
                ))
            );
        }

        return $inflector;
    }
}
