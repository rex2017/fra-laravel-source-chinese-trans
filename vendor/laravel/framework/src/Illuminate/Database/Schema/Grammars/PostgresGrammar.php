<?php
/**
 * 数据库，Postgres语法
 */

namespace Illuminate\Database\Schema\Grammars;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Fluent;

class PostgresGrammar extends Grammar
{
    /**
     * If this Grammar supports schema changes wrapped in a transaction.
	 * 是否此语法支持封装在事务中的模式更改
     *
     * @var bool
     */
    protected $transactions = true;

    /**
     * The possible column modifiers.
	 * 可能的列修改
     *
     * @var array
     */
    protected $modifiers = ['Collate', 'Increment', 'Nullable', 'Default', 'VirtualAs', 'StoredAs'];

    /**
     * The columns available as serials.
	 * 作为序列可用的列
     *
     * @var array
     */
    protected $serials = ['bigInteger', 'integer', 'mediumInteger', 'smallInteger', 'tinyInteger'];

    /**
     * The commands to be executed outside of create or alter command.
	 * 要在create或alter命令之外执行的命令
     *
     * @var array
     */
    protected $fluentCommands = ['Comment'];

    /**
     * Compile the query to determine if a table exists.
	 * 编译查询以确定表是否存在
     *
     * @return string
     */
    public function compileTableExists()
    {
        return "select * from information_schema.tables where table_schema = ? and table_name = ? and table_type = 'BASE TABLE'";
    }

    /**
     * Compile the query to determine the list of columns.
	 * 编译查询以确定表是否存在
     *
     * @return string
     */
    public function compileColumnListing()
    {
        return 'select column_name from information_schema.columns where table_schema = ? and table_name = ?';
    }

    /**
     * Compile a create table command.
	 * 编译一个create table命令
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileCreate(Blueprint $blueprint, Fluent $command)
    {
        return sprintf('%s table %s (%s)',
            $blueprint->temporary ? 'create temporary' : 'create',
            $this->wrapTable($blueprint),
            implode(', ', $this->getColumns($blueprint))
        );
    }

    /**
     * Compile a column addition command.
	 * 编译列添加命令
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileAdd(Blueprint $blueprint, Fluent $command)
    {
        return sprintf('alter table %s %s',
            $this->wrapTable($blueprint),
            implode(', ', $this->prefixArray('add column', $this->getColumns($blueprint)))
        );
    }

    /**
     * Compile a primary key command.
	 * 编译主键命令
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compilePrimary(Blueprint $blueprint, Fluent $command)
    {
        $columns = $this->columnize($command->columns);

        return 'alter table '.$this->wrapTable($blueprint)." add primary key ({$columns})";
    }

    /**
     * Compile a unique key command.
	 * 编译唯一的密钥命令
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileUnique(Blueprint $blueprint, Fluent $command)
    {
        return sprintf('alter table %s add constraint %s unique (%s)',
            $this->wrapTable($blueprint),
            $this->wrap($command->index),
            $this->columnize($command->columns)
        );
    }

    /**
     * Compile a plain index key command.
	 * 编译一个普通索引键命令
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileIndex(Blueprint $blueprint, Fluent $command)
    {
        return sprintf('create index %s on %s%s (%s)',
            $this->wrap($command->index),
            $this->wrapTable($blueprint),
            $command->algorithm ? ' using '.$command->algorithm : '',
            $this->columnize($command->columns)
        );
    }

    /**
     * Compile a spatial index key command.
	 * 编译一个空间索引键命令
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileSpatialIndex(Blueprint $blueprint, Fluent $command)
    {
        $command->algorithm = 'gist';

        return $this->compileIndex($blueprint, $command);
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
        $sql = parent::compileForeign($blueprint, $command);

        if (! is_null($command->deferrable)) {
            $sql .= $command->deferrable ? ' deferrable' : ' not deferrable';
        }

        if ($command->deferrable && ! is_null($command->initiallyImmediate)) {
            $sql .= $command->initiallyImmediate ? ' initially immediate' : ' initially deferred';
        }

        if (! is_null($command->notValid)) {
            $sql .= ' not valid';
        }

        return $sql;
    }

    /**
     * Compile a drop table command.
	 * 编译删除表命令
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileDrop(Blueprint $blueprint, Fluent $command)
    {
        return 'drop table '.$this->wrapTable($blueprint);
    }

    /**
     * Compile a drop table (if exists) command.
	 * 编译删除表命令(如果存在)
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileDropIfExists(Blueprint $blueprint, Fluent $command)
    {
        return 'drop table if exists '.$this->wrapTable($blueprint);
    }

    /**
     * Compile the SQL needed to drop all tables.
	 * 编译删除所有表所需的SQL
     *
     * @param  array  $tables
     * @return string
     */
    public function compileDropAllTables($tables)
    {
        return 'drop table "'.implode('","', $tables).'" cascade';
    }

    /**
     * Compile the SQL needed to drop all views.
	 * 编译删除所有视图所需的SQL
     *
     * @param  array  $views
     * @return string
     */
    public function compileDropAllViews($views)
    {
        return 'drop view "'.implode('","', $views).'" cascade';
    }

    /**
     * Compile the SQL needed to drop all types.
	 * 编译删除所有类型所需的SQL
     *
     * @param  array  $types
     * @return string
     */
    public function compileDropAllTypes($types)
    {
        return 'drop type "'.implode('","', $types).'" cascade';
    }

    /**
     * Compile the SQL needed to retrieve all table names.
	 * 编译检索所有表名所需的SQL
     *
     * @param  string|array  $schema
     * @return string
     */
    public function compileGetAllTables($schema)
    {
        return "select tablename from pg_catalog.pg_tables where schemaname in ('".implode("','", (array) $schema)."')";
    }

    /**
     * Compile the SQL needed to retrieve all view names.
	 * 编译检索所有视图名所需的SQL
     *
     * @param  string|array  $schema
     * @return string
     */
    public function compileGetAllViews($schema)
    {
        return "select viewname from pg_catalog.pg_views where schemaname in ('".implode("','", (array) $schema)."')";
    }

    /**
     * Compile the SQL needed to retrieve all type names.
	 * 编译检索所有类型名称所需的SQL
     *
     * @return string
     */
    public function compileGetAllTypes()
    {
        return 'select distinct pg_type.typname from pg_type inner join pg_enum on pg_enum.enumtypid = pg_type.oid';
    }

    /**
     * Compile a drop column command.
	 * 编译删除列命令
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileDropColumn(Blueprint $blueprint, Fluent $command)
    {
        $columns = $this->prefixArray('drop column', $this->wrapArray($command->columns));

        return 'alter table '.$this->wrapTable($blueprint).' '.implode(', ', $columns);
    }

    /**
     * Compile a drop primary key command.
	 * 编译删除主键命令
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileDropPrimary(Blueprint $blueprint, Fluent $command)
    {
        $index = $this->wrap("{$blueprint->getTable()}_pkey");

        return 'alter table '.$this->wrapTable($blueprint)." drop constraint {$index}";
    }

    /**
     * Compile a drop unique key command.
	 * 编译删除唯一键命令
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileDropUnique(Blueprint $blueprint, Fluent $command)
    {
        $index = $this->wrap($command->index);

        return "alter table {$this->wrapTable($blueprint)} drop constraint {$index}";
    }

    /**
     * Compile a drop index command.
	 * 编译删除索引命令
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileDropIndex(Blueprint $blueprint, Fluent $command)
    {
        return "drop index {$this->wrap($command->index)}";
    }

    /**
     * Compile a drop spatial index command.
	 * 编译删除空间索引命令
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileDropSpatialIndex(Blueprint $blueprint, Fluent $command)
    {
        return $this->compileDropIndex($blueprint, $command);
    }

    /**
     * Compile a drop foreign key command.
	 * 编译删除外键命令
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileDropForeign(Blueprint $blueprint, Fluent $command)
    {
        $index = $this->wrap($command->index);

        return "alter table {$this->wrapTable($blueprint)} drop constraint {$index}";
    }

    /**
     * Compile a rename table command.
	 * 编译重命名表命令
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileRename(Blueprint $blueprint, Fluent $command)
    {
        $from = $this->wrapTable($blueprint);

        return "alter table {$from} rename to ".$this->wrapTable($command->to);
    }

    /**
     * Compile a rename index command.
	 * 编译重命名索引命令
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileRenameIndex(Blueprint $blueprint, Fluent $command)
    {
        return sprintf('alter index %s rename to %s',
            $this->wrap($command->from),
            $this->wrap($command->to)
        );
    }

    /**
     * Compile the command to enable foreign key constraints.
	 * 编译命令以启用外键约束
     *
     * @return string
     */
    public function compileEnableForeignKeyConstraints()
    {
        return 'SET CONSTRAINTS ALL IMMEDIATE;';
    }

    /**
     * Compile the command to disable foreign key constraints.
	 * 编译命令以禁用外键约束
     *
     * @return string
     */
    public function compileDisableForeignKeyConstraints()
    {
        return 'SET CONSTRAINTS ALL DEFERRED;';
    }

    /**
     * Compile a comment command.
	 * 编译注释命令
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileComment(Blueprint $blueprint, Fluent $command)
    {
        return sprintf('comment on column %s.%s is %s',
            $this->wrapTable($blueprint),
            $this->wrap($command->column->name),
            "'".str_replace("'", "''", $command->value)."'"
        );
    }

    /**
     * Create the column definition for a char type.
	 * 创建列定义为char类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeChar(Fluent $column)
    {
        return "char({$column->length})";
    }

    /**
     * Create the column definition for a string type.
	 * 创建列定义为字符串类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeString(Fluent $column)
    {
        return "varchar({$column->length})";
    }

    /**
     * Create the column definition for a text type.
	 * 创建列定义为文本类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeText(Fluent $column)
    {
        return 'text';
    }

    /**
     * Create the column definition for a medium text type.
	 *创建列定义为中等文本类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeMediumText(Fluent $column)
    {
        return 'text';
    }

    /**
     * Create the column definition for a long text type.
	 * 创建列定义为长文本类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeLongText(Fluent $column)
    {
        return 'text';
    }

    /**
     * Create the column definition for an integer type.
	 * 创建列定义为整数类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeInteger(Fluent $column)
    {
        return $this->generatableColumn('integer', $column);
    }

    /**
     * Create the column definition for a big integer type.
	 * 创建列定义为大整数类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeBigInteger(Fluent $column)
    {
        return $this->generatableColumn('bigint', $column);
    }

    /**
     * Create the column definition for a medium integer type.
	 * 创建列定义为中等整数类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeMediumInteger(Fluent $column)
    {
        return $this->generatableColumn('integer', $column);
    }

    /**
     * Create the column definition for a tiny integer type.
	 * 创建列定义为一个小整数类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeTinyInteger(Fluent $column)
    {
        return $this->generatableColumn('smallint', $column);
    }

    /**
     * Create the column definition for a small integer type.
	 * 创建列定义为小整数类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeSmallInteger(Fluent $column)
    {
        return $this->generatableColumn('smallint', $column);
    }

    /**
     * Create the column definition for a generatable column.
	 * 创建列定义为可生成的列
     *
     * @param  string  $type
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function generatableColumn($type, Fluent $column)
    {
        if (! $column->autoIncrement && is_null($column->generatedAs)) {
            return $type;
        }

        if ($column->autoIncrement && is_null($column->generatedAs)) {
            return with([
                'integer' => 'serial',
                'bigint' => 'bigserial',
                'smallint' => 'smallserial',
            ])[$type];
        }

        $options = '';

        if (! is_bool($column->generatedAs) && ! empty($column->generatedAs)) {
            $options = sprintf(' (%s)', $column->generatedAs);
        }

        return sprintf(
            '%s generated %s as identity%s',
            $type,
            $column->always ? 'always' : 'by default',
            $options
        );
    }

    /**
     * Create the column definition for a float type.
	 * 创建列定义为float类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeFloat(Fluent $column)
    {
        return $this->typeDouble($column);
    }

    /**
     * Create the column definition for a double type.
	 * 创建双类型的列定义
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeDouble(Fluent $column)
    {
        return 'double precision';
    }

    /**
     * Create the column definition for a real type.
	 * 创建列定义为实际类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeReal(Fluent $column)
    {
        return 'real';
    }

    /**
     * Create the column definition for a decimal type.
	 * 创建列定义为十进制类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeDecimal(Fluent $column)
    {
        return "decimal({$column->total}, {$column->places})";
    }

    /**
     * Create the column definition for a boolean type.
	 * 创建列定义为布尔类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeBoolean(Fluent $column)
    {
        return 'boolean';
    }

    /**
     * Create the column definition for an enumeration type.
	 * 创建列定义为枚举类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeEnum(Fluent $column)
    {
        return sprintf(
            'varchar(255) check ("%s" in (%s))',
            $column->name,
            $this->quoteString($column->allowed)
        );
    }

    /**
     * Create the column definition for a json type.
	 * 创建列定义为json类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeJson(Fluent $column)
    {
        return 'json';
    }

    /**
     * Create the column definition for a jsonb type.
	 * 创建列定义为jsonb类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeJsonb(Fluent $column)
    {
        return 'jsonb';
    }

    /**
     * Create the column definition for a date type.
	 * 创建列定义为日期类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeDate(Fluent $column)
    {
        return 'date';
    }

    /**
     * Create the column definition for a date-time type.
	 * 创建列定义为日期-时间类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeDateTime(Fluent $column)
    {
        return $this->typeTimestamp($column);
    }

    /**
     * Create the column definition for a date-time (with time zone) type.
	 * 创建列定义为日期-时间(带时区)类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeDateTimeTz(Fluent $column)
    {
        return $this->typeTimestampTz($column);
    }

    /**
     * Create the column definition for a time type.
	 * 创建时间类型的列定义
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeTime(Fluent $column)
    {
        return 'time'.(is_null($column->precision) ? '' : "($column->precision)").' without time zone';
    }

    /**
     * Create the column definition for a time (with time zone) type.
	 * 创建列定义为时间(带时区)类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeTimeTz(Fluent $column)
    {
        return 'time'.(is_null($column->precision) ? '' : "($column->precision)").' with time zone';
    }

    /**
     * Create the column definition for a timestamp type.
	 * 创建列定义为时间戳类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeTimestamp(Fluent $column)
    {
        $columnType = 'timestamp'.(is_null($column->precision) ? '' : "($column->precision)").' without time zone';

        return $column->useCurrent ? "$columnType default CURRENT_TIMESTAMP" : $columnType;
    }

    /**
     * Create the column definition for a timestamp (with time zone) type.
	 * 创建列定义为时间戳(带时区)类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeTimestampTz(Fluent $column)
    {
        $columnType = 'timestamp'.(is_null($column->precision) ? '' : "($column->precision)").' with time zone';

        return $column->useCurrent ? "$columnType default CURRENT_TIMESTAMP" : $columnType;
    }

    /**
     * Create the column definition for a year type.
	 * 创建年份类型的列定义
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeYear(Fluent $column)
    {
        return $this->typeInteger($column);
    }

    /**
     * Create the column definition for a binary type.
	 * 创建列定义为二进制类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeBinary(Fluent $column)
    {
        return 'bytea';
    }

    /**
     * Create the column definition for a uuid type.
	 * 创建列定义为uid类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeUuid(Fluent $column)
    {
        return 'uuid';
    }

    /**
     * Create the column definition for an IP address type.
	 * 创建列定义为IP地址类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeIpAddress(Fluent $column)
    {
        return 'inet';
    }

    /**
     * Create the column definition for a MAC address type.
	 * 创建MAC地址类型的列定义
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeMacAddress(Fluent $column)
    {
        return 'macaddr';
    }

    /**
     * Create the column definition for a spatial Geometry type.
	 * 创建列定义为空间几何类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeGeometry(Fluent $column)
    {
        return $this->formatPostGisType('geometry', $column);
    }

    /**
     * Create the column definition for a spatial Point type.
	 * 创建列定义为空间Point类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typePoint(Fluent $column)
    {
        return $this->formatPostGisType('point', $column);
    }

    /**
     * Create the column definition for a spatial LineString type.
	 * 创建列定义为空间LineString类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeLineString(Fluent $column)
    {
        return $this->formatPostGisType('linestring', $column);
    }

    /**
     * Create the column definition for a spatial Polygon type.
	 * 创建列定义为空间多边形类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typePolygon(Fluent $column)
    {
        return $this->formatPostGisType('polygon', $column);
    }

    /**
     * Create the column definition for a spatial GeometryCollection type.
	 * 创建列定义为空间GeometryCollection类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeGeometryCollection(Fluent $column)
    {
        return $this->formatPostGisType('geometrycollection', $column);
    }

    /**
     * Create the column definition for a spatial MultiPoint type.
	 * 创建列定义为空间多点类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeMultiPoint(Fluent $column)
    {
        return $this->formatPostGisType('multipoint', $column);
    }

    /**
     * Create the column definition for a spatial MultiLineString type.
	 * 创建列定义为空间MultiLineString类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    public function typeMultiLineString(Fluent $column)
    {
        return $this->formatPostGisType('multilinestring', $column);
    }

    /**
     * Create the column definition for a spatial MultiPolygon type.
	 * 创建列定义为空间MultiPolygon类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeMultiPolygon(Fluent $column)
    {
        return $this->formatPostGisType('multipolygon', $column);
    }

    /**
     * Create the column definition for a spatial MultiPolygonZ type.
	 * 创建列定义为空间MultiPolygonZ类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeMultiPolygonZ(Fluent $column)
    {
        return $this->formatPostGisType('multipolygonz', $column);
    }

    /**
     * Format the column definition for a PostGIS spatial type.
	 * 格式化PostGIS空间类型的列定义
     *
     * @param  string  $type
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    private function formatPostGisType($type, Fluent $column)
    {
        if ($column->isGeometry === null) {
            return sprintf('geography(%s, %s)', $type, $column->projection ?? '4326');
        }

        if ($column->projection !== null) {
            return sprintf('geometry(%s, %s)', $type, $column->projection);
        }

        return "geometry({$type})";
    }

    /**
     * Get the SQL for a collation column modifier.
	 * 设置排序列修饰符的SQL
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $column
     * @return string|null
     */
    protected function modifyCollate(Blueprint $blueprint, Fluent $column)
    {
        if (! is_null($column->collation)) {
            return ' collate '.$this->wrapValue($column->collation);
        }
    }

    /**
     * Get the SQL for a nullable column modifier.
	 * 得到可空列修饰符的SQL
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $column
     * @return string|null
     */
    protected function modifyNullable(Blueprint $blueprint, Fluent $column)
    {
        return $column->nullable ? ' null' : ' not null';
    }

    /**
     * Get the SQL for a default column modifier.
	 * 得到默认列修饰符的SQL
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $column
     * @return string|null
     */
    protected function modifyDefault(Blueprint $blueprint, Fluent $column)
    {
        if (! is_null($column->default)) {
            return ' default '.$this->getDefaultValue($column->default);
        }
    }

    /**
     * Get the SQL for an auto-increment column modifier.
	 * 得到用于自动增量列修饰符的SQL
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $column
     * @return string|null
     */
    protected function modifyIncrement(Blueprint $blueprint, Fluent $column)
    {
        if ((in_array($column->type, $this->serials) || ($column->generatedAs !== null)) && $column->autoIncrement) {
            return ' primary key';
        }
    }

    /**
     * Get the SQL for a generated virtual column modifier.
	 * 得到生成的虚拟列修饰符的SQL
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $column
     * @return string|null
     */
    protected function modifyVirtualAs(Blueprint $blueprint, Fluent $column)
    {
        if ($column->virtualAs !== null) {
            return " generated always as ({$column->virtualAs})";
        }
    }

    /**
     * Get the SQL for a generated stored column modifier.
	 * 得到生成的存储列修饰符的SQL
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $column
     * @return string|null
     */
    protected function modifyStoredAs(Blueprint $blueprint, Fluent $column)
    {
        if ($column->storedAs !== null) {
            return " generated always as ({$column->storedAs}) stored";
        }
    }
}
