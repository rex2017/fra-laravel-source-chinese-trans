<?php
/**
 * 数据库，语法抽象类
 */

namespace Illuminate\Database\Schema\Grammars;

use Doctrine\DBAL\Schema\AbstractSchemaManager as SchemaManager;
use Doctrine\DBAL\Schema\TableDiff;
use Illuminate\Database\Connection;
use Illuminate\Database\Grammar as BaseGrammar;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Fluent;
use RuntimeException;

abstract class Grammar extends BaseGrammar
{
    /**
     * If this Grammar supports schema changes wrapped in a transaction.
	 * 是否此语法支持封装在事务中的模式更改
     *
     * @var bool
     */
    protected $transactions = false;

    /**
     * The commands to be executed outside of create or alter command.
	 * 要在create或alter命令之外执行的命令
     *
     * @var array
     */
    protected $fluentCommands = [];

    /**
     * Compile a rename column command.
	 * 编译重命名列命令
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @param  \Illuminate\Database\Connection  $connection
     * @return array
     */
    public function compileRenameColumn(Blueprint $blueprint, Fluent $command, Connection $connection)
    {
        return RenameColumn::compile($this, $blueprint, $command, $connection);
    }

    /**
     * Compile a change column command into a series of SQL statements.
	 * 编译更改列命令成一系列SQL语句
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @param  \Illuminate\Database\Connection  $connection
     * @return array
     *
     * @throws \RuntimeException
     */
    public function compileChange(Blueprint $blueprint, Fluent $command, Connection $connection)
    {
        return ChangeColumn::compile($this, $blueprint, $command, $connection);
    }

    /**
     * Compile a foreign key command.
	 * 编译外键命令
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileForeign(Blueprint $blueprint, Fluent $command)
    {
        // We need to prepare several of the elements of the foreign key definition
        // before we can create the SQL, such as wrapping the tables and convert
        // an array of columns to comma-delimited strings for the SQL queries.
		// 在创建SQL之前，我们需要准备外键定义的几个元素，例如包装表并将列数组转换为逗号分隔的字符串以用于SQL查询。
        $sql = sprintf('alter table %s add constraint %s ',
            $this->wrapTable($blueprint),
            $this->wrap($command->index)
        );

        // Once we have the initial portion of the SQL statement we will add on the
        // key name, table name, and referenced columns. These will complete the
        // main portion of the SQL statement and this SQL will almost be done.
		// 一旦我们有了SQL语句的初始部分，我们将添加键名、表名和引用的列。
		// 这些将完成SQL语句的主要部分，而这个SQL几乎就要完成了。
        $sql .= sprintf('foreign key (%s) references %s (%s)',
            $this->columnize($command->columns),
            $this->wrapTable($command->on),
            $this->columnize((array) $command->references)
        );

        // Once we have the basic foreign key creation statement constructed we can
        // build out the syntax for what should happen on an update or delete of
        // the affected columns, which will get something like "cascade", etc.
		// 一旦我们构造了基本的外键创建语句，我们就可以构建出在更新或删除受影响的列时应该发生的语法，
		// 这将得到类似"级联"等的结果。
        if (! is_null($command->onDelete)) {
            $sql .= " on delete {$command->onDelete}";
        }

        if (! is_null($command->onUpdate)) {
            $sql .= " on update {$command->onUpdate}";
        }

        return $sql;
    }

    /**
     * Compile the blueprint's column definitions.
	 * 编译蓝图的列定义
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @return array
     */
    protected function getColumns(Blueprint $blueprint)
    {
        $columns = [];

        foreach ($blueprint->getAddedColumns() as $column) {
            // Each of the column types have their own compiler functions which are tasked
            // with turning the column definition into its SQL format for this platform
            // used by the connection. The column's modifiers are compiled and added.
			// 每种列类型都有自己的编译器函数，其任务是将列定义转换为连接所使用的此平台的SQL格式。
			// 列的修饰符被编译和添加。
            $sql = $this->wrap($column).' '.$this->getType($column);

            $columns[] = $this->addModifiers($sql, $blueprint, $column);
        }

        return $columns;
    }

    /**
     * Get the SQL for the column data type.
	 * 得到列数据类型的SQL
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function getType(Fluent $column)
    {
        return $this->{'type'.ucfirst($column->type)}($column);
    }

    /**
     * Create the column definition for a generated, computed column type.
	 * 为生成的、计算的列类型创建列定义。
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return void
     *
     * @throws \RuntimeException
     */
    protected function typeComputed(Fluent $column)
    {
        throw new RuntimeException('This database driver does not support the computed type.');
    }

    /**
     * Add the column modifiers to the definition.
	 * 添加列修饰符到定义中
     *
     * @param  string  $sql
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function addModifiers($sql, Blueprint $blueprint, Fluent $column)
    {
        foreach ($this->modifiers as $modifier) {
            if (method_exists($this, $method = "modify{$modifier}")) {
                $sql .= $this->{$method}($blueprint, $column);
            }
        }

        return $sql;
    }

    /**
     * Get the primary key command if it exists on the blueprint.
	 * 得到主键命令如果蓝图上存在主键命令
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  string  $name
     * @return \Illuminate\Support\Fluent|null
     */
    protected function getCommandByName(Blueprint $blueprint, $name)
    {
        $commands = $this->getCommandsByName($blueprint, $name);

        if (count($commands) > 0) {
            return reset($commands);
        }
    }

    /**
     * Get all of the commands with a given name.
	 * 得到具有给定名称的所有命令
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  string  $name
     * @return array
     */
    protected function getCommandsByName(Blueprint $blueprint, $name)
    {
        return array_filter($blueprint->getCommands(), function ($value) use ($name) {
            return $value->name == $name;
        });
    }

    /**
     * Add a prefix to an array of values.
	 * 添加前缀至数组值
     *
     * @param  string  $prefix
     * @param  array  $values
     * @return array
     */
    public function prefixArray($prefix, array $values)
    {
        return array_map(function ($value) use ($prefix) {
            return $prefix.' '.$value;
        }, $values);
    }

    /**
     * Wrap a table in keyword identifiers.
	 * 包装表用关键字标识符
     *
     * @param  mixed  $table
     * @return string
     */
    public function wrapTable($table)
    {
        return parent::wrapTable(
            $table instanceof Blueprint ? $table->getTable() : $table
        );
    }

    /**
     * Wrap a value in keyword identifiers.
	 * 包装值用关键字标识符
     *
     * @param  \Illuminate\Database\Query\Expression|string  $value
     * @param  bool  $prefixAlias
     * @return string
     */
    public function wrap($value, $prefixAlias = false)
    {
        return parent::wrap(
            $value instanceof Fluent ? $value->name : $value, $prefixAlias
        );
    }

    /**
     * Format a value so that it can be used in "default" clauses.
	 * 格式化一个值，以便它可以在"default"子句中使用。
     *
     * @param  mixed  $value
     * @return string
     */
    protected function getDefaultValue($value)
    {
        if ($value instanceof Expression) {
            return $value;
        }

        return is_bool($value)
                    ? "'".(int) $value."'"
                    : "'".(string) $value."'";
    }

    /**
     * Create an empty Doctrine DBAL TableDiff from the Blueprint.
	 * 从蓝图中创建一个空的Doctrine DBAL TableDiff
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Doctrine\DBAL\Schema\AbstractSchemaManager  $schema
     * @return \Doctrine\DBAL\Schema\TableDiff
     */
    public function getDoctrineTableDiff(Blueprint $blueprint, SchemaManager $schema)
    {
        $table = $this->getTablePrefix().$blueprint->getTable();

        return tap(new TableDiff($table), function ($tableDiff) use ($schema, $table) {
            $tableDiff->fromTable = $schema->listTableDetails($table);
        });
    }

    /**
     * Get the fluent commands for the grammar.
	 * 得到流利的语法命令
     *
     * @return array
     */
    public function getFluentCommands()
    {
        return $this->fluentCommands;
    }

    /**
     * Check if this Grammar supports schema changes wrapped in a transaction.
	 * 检查此语法是否支持封装在事务中的模式更改
     *
     * @return bool
     */
    public function supportsSchemaTransactions()
    {
        return $this->transactions;
    }
}
