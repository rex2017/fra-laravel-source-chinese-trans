<?php
/**
 * 支持，门面架构
 */

namespace Illuminate\Support\Facades;

/**
 * @method static \Illuminate\Database\Schema\Builder create(string $table, \Closure $callback)
 * @method static \Illuminate\Database\Schema\Builder drop(string $table)
 * @method static \Illuminate\Database\Schema\Builder dropIfExists(string $table)
 * @method static \Illuminate\Database\Schema\Builder table(string $table, \Closure $callback)
 * @method static \Illuminate\Database\Schema\Builder rename(string $from, string $to)
 * @method static void defaultStringLength(int $length)
 * @method static bool hasTable(string $table)
 * @method static bool hasColumn(string $table, string $column)
 * @method static bool hasColumns(string $table, array $columns)
 * @method static \Illuminate\Database\Schema\Builder disableForeignKeyConstraints()
 * @method static \Illuminate\Database\Schema\Builder enableForeignKeyConstraints()
 * @method static void registerCustomDoctrineType(string $class, string $name, string $type)
 *
 * @see \Illuminate\Database\Schema\Builder
 */
class Schema extends Facade
{
    /**
     * Get a schema builder instance for a connection.
	 * 得到连接的架构构建器实例
     *
     * @param  string|null  $name
     * @return \Illuminate\Database\Schema\Builder
     */
    public static function connection($name)
    {
        return static::$app['db']->connection($name)->getSchemaBuilder();
    }

    /**
     * Get a schema builder instance for the default connection.
	 * 得到默认连接的架构构建器实例
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    protected static function getFacadeAccessor()
    {
        return static::$app['db']->connection()->getSchemaBuilder();
    }
}
