<?php
/**
 * 数据库，结构建立者
 */

namespace Illuminate\Database\Schema;

use Closure;
use Doctrine\DBAL\Types\Type;
use Illuminate\Database\Connection;
use LogicException;
use RuntimeException;

class Builder
{
    /**
     * The database connection instance.
	 * 数据库连接实例
     *
     * @var \Illuminate\Database\Connection
     */
    protected $connection;

    /**
     * The schema grammar instance.
	 * 模式语法实例
     *
     * @var \Illuminate\Database\Schema\Grammars\Grammar
     */
    protected $grammar;

    /**
     * The Blueprint resolver callback.
	 * 蓝图解析回调
     *
     * @var \Closure
     */
    protected $resolver;

    /**
     * The default string length for migrations.
	 * 默认字符串长度
     *
     * @var int
     */
    public static $defaultStringLength = 255;

    /**
     * Create a new database Schema manager.
	 * 创建新的数据库模式管理器
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @return void
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->grammar = $connection->getSchemaGrammar();
    }

    /**
     * Set the default string length for migrations.
	 * 设置迁移的默认字符串长度
     *
     * @param  int  $length
     * @return void
     */
    public static function defaultStringLength($length)
    {
        static::$defaultStringLength = $length;
    }

    /**
     * Determine if the given table exists.
	 * 确定给定表是否存在
     *
     * @param  string  $table
     * @return bool
     */
    public function hasTable($table)
    {
        $table = $this->connection->getTablePrefix().$table;

        return count($this->connection->selectFromWriteConnection(
            $this->grammar->compileTableExists(), [$table]
        )) > 0;
    }

    /**
     * Determine if the given table has a given column.
	 * 确定给定表是否有给定列
     *
     * @param  string  $table
     * @param  string  $column
     * @return bool
     */
    public function hasColumn($table, $column)
    {
        return in_array(
            strtolower($column), array_map('strtolower', $this->getColumnListing($table))
        );
    }

    /**
     * Determine if the given table has given columns.
	 * 确定给定表是否有给定列
     *
     * @param  string  $table
     * @param  array  $columns
     * @return bool
     */
    public function hasColumns($table, array $columns)
    {
        $tableColumns = array_map('strtolower', $this->getColumnListing($table));

        foreach ($columns as $column) {
            if (! in_array(strtolower($column), $tableColumns)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the data type for the given column name.
	 * 得到给定列名的数据类型
     *
     * @param  string  $table
     * @param  string  $column
     * @return string
     */
    public function getColumnType($table, $column)
    {
        $table = $this->connection->getTablePrefix().$table;

        return $this->connection->getDoctrineColumn($table, $column)->getType()->getName();
    }

    /**
     * Get the column listing for a given table.
	 * 得到给定表的列清单
     *
     * @param  string  $table
     * @return array
     */
    public function getColumnListing($table)
    {
        $results = $this->connection->selectFromWriteConnection($this->grammar->compileColumnListing(
            $this->connection->getTablePrefix().$table
        ));

        return $this->connection->getPostProcessor()->processColumnListing($results);
    }

    /**
     * Modify a table on the schema.
	 * 修改模式上的表
     *
     * @param  string  $table
     * @param  \Closure  $callback
     * @return void
     */
    public function table($table, Closure $callback)
    {
        $this->build($this->createBlueprint($table, $callback));
    }

    /**
     * Create a new table on the schema.
	 * 创建一个新表在模式上
     *
     * @param  string  $table
     * @param  \Closure  $callback
     * @return void
     */
    public function create($table, Closure $callback)
    {
        $this->build(tap($this->createBlueprint($table), function ($blueprint) use ($callback) {
            $blueprint->create();

            $callback($blueprint);
        }));
    }

    /**
     * Drop a table from the schema.
	 * 删除一个表从模式中
     *
     * @param  string  $table
     * @return void
     */
    public function drop($table)
    {
        $this->build(tap($this->createBlueprint($table), function ($blueprint) {
            $blueprint->drop();
        }));
    }

    /**
     * Drop a table from the schema if it exists.
	 * 删除一个表从模式中如果表存在
     *
     * @param  string  $table
     * @return void
     */
    public function dropIfExists($table)
    {
        $this->build(tap($this->createBlueprint($table), function ($blueprint) {
            $blueprint->dropIfExists();
        }));
    }

    /**
     * Drop all tables from the database.
	 * 删除所有表从数据库中
     *
     * @return void
     *
     * @throws \LogicException
     */
    public function dropAllTables()
    {
        throw new LogicException('This database driver does not support dropping all tables.');
    }

    /**
     * Drop all views from the database.
	 * 删除所有视图从数据库中
     *
     * @return void
     *
     * @throws \LogicException
     */
    public function dropAllViews()
    {
        throw new LogicException('This database driver does not support dropping all views.');
    }

    /**
     * Drop all types from the database.
	 * 删除所有类型从数据库中
     *
     * @return void
     *
     * @throws \LogicException
     */
    public function dropAllTypes()
    {
        throw new LogicException('This database driver does not support dropping all types.');
    }

    /**
     * Get all of the table names for the database.
	 * 得到所有表名从数据库中
     *
     * @return void
     *
     * @throws \LogicException
     */
    public function getAllTables()
    {
        throw new LogicException('This database driver does not support getting all tables.');
    }

    /**
     * Rename a table on the schema.
	 * 重命名模式上的表
     *
     * @param  string  $from
     * @param  string  $to
     * @return void
     */
    public function rename($from, $to)
    {
        $this->build(tap($this->createBlueprint($from), function ($blueprint) use ($to) {
            $blueprint->rename($to);
        }));
    }

    /**
     * Enable foreign key constraints.
	 * 启用外键约束
     *
     * @return bool
     */
    public function enableForeignKeyConstraints()
    {
        return $this->connection->statement(
            $this->grammar->compileEnableForeignKeyConstraints()
        );
    }

    /**
     * Disable foreign key constraints.
	 * 禁用外键约束
     *
     * @return bool
     */
    public function disableForeignKeyConstraints()
    {
        return $this->connection->statement(
            $this->grammar->compileDisableForeignKeyConstraints()
        );
    }

    /**
     * Execute the blueprint to build / modify the table.
	 * 执行蓝图来构建/修改表
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @return void
     */
    protected function build(Blueprint $blueprint)
    {
        $blueprint->build($this->connection, $this->grammar);
    }

    /**
     * Create a new command set with a Closure.
	 * 创建新的命令集使用Closure
     *
     * @param  string  $table
     * @param  \Closure|null  $callback
     * @return \Illuminate\Database\Schema\Blueprint
     */
    protected function createBlueprint($table, Closure $callback = null)
    {
        $prefix = $this->connection->getConfig('prefix_indexes')
                    ? $this->connection->getConfig('prefix')
                    : '';

        if (isset($this->resolver)) {
            return call_user_func($this->resolver, $table, $callback, $prefix);
        }

        return new Blueprint($table, $callback, $prefix);
    }

    /**
     * Register a custom Doctrine mapping type.
	 * 注册一个自定义Doctrine映射类型
     *
     * @param  string  $class
     * @param  string  $name
     * @param  string  $type
     * @return void
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \RuntimeException
     */
    public function registerCustomDoctrineType($class, $name, $type)
    {
        if (! $this->connection->isDoctrineAvailable()) {
            throw new RuntimeException(
                'Registering a custom Doctrine type requires Doctrine DBAL (doctrine/dbal).'
            );
        }

        if (! Type::hasType($name)) {
            Type::addType($name, $class);

            $this->connection
                ->getDoctrineSchemaManager()
                ->getDatabasePlatform()
                ->registerDoctrineTypeMapping($type, $name);
        }
    }

    /**
     * Get the database connection instance.
	 * 得到数据库连接实例
     *
     * @return \Illuminate\Database\Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Set the database connection instance.
	 * 设置数据库连接实例
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @return $this
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Set the Schema Blueprint resolver callback.
	 * 设置架构蓝图解析器回调
     *
     * @param  \Closure  $resolver
     * @return void
     */
    public function blueprintResolver(Closure $resolver)
    {
        $this->resolver = $resolver;
    }
}
