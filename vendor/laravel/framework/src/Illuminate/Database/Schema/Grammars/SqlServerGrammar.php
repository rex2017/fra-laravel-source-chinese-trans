<?php
/**
 * 数据库，SqlServer语法
 */

namespace Illuminate\Database\Schema\Grammars;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Fluent;

class SqlServerGrammar extends Grammar
{
    /**
     * If this Grammar supports schema changes wrapped in a transaction.
	 * 如果此语法支持封装在事务中的模式更改
     *
     * @var bool
     */
    protected $transactions = true;

    /**
     * The possible column modifiers.
	 * 可能的列修饰符
     *
     * @var array
     */
    protected $modifiers = ['Increment', 'Collate', 'Nullable', 'Default', 'Persisted'];

    /**
     * The columns available as serials.
	 * 作为序列可用的列
     *
     * @var array
     */
    protected $serials = ['tinyInteger', 'smallInteger', 'mediumInteger', 'integer', 'bigInteger'];

    /**
     * Compile the query to determine if a table or view exists.
	 * 编译查询以确定是否存在表或视图
     *
     * @return string
     */
    public function compileTableExists()
    {
        return "select * from sysobjects where type in ('U', 'V') and name = ?";
    }

    /**
     * Compile the query to determine the list of columns.
	 * 编译查询以确定列列表
     *
     * @param  string  $table
     * @return string
     */
    public function compileColumnListing($table)
    {
        return "select col.name from sys.columns as col
                join sys.objects as obj on col.object_id = obj.object_id
                where obj.type in ('U', 'V') and obj.name = '$table'";
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
        $columns = implode(', ', $this->getColumns($blueprint));

        return 'create table '.$this->wrapTable($blueprint)." ($columns)";
    }

    /**
     * Compile a column addition table command.
	 * 编译列添加表命令
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileAdd(Blueprint $blueprint, Fluent $command)
    {
        return sprintf('alter table %s add %s',
            $this->wrapTable($blueprint),
            implode(', ', $this->getColumns($blueprint))
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
        return sprintf('alter table %s add constraint %s primary key (%s)',
            $this->wrapTable($blueprint),
            $this->wrap($command->index),
            $this->columnize($command->columns)
        );
    }

    /**
     * Compile a unique key command.
	 * 编译唯一键命令
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileUnique(Blueprint $blueprint, Fluent $command)
    {
        return sprintf('create unique index %s on %s (%s)',
            $this->wrap($command->index),
            $this->wrapTable($blueprint),
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
        return sprintf('create index %s on %s (%s)',
            $this->wrap($command->index),
            $this->wrapTable($blueprint),
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
        return sprintf('create spatial index %s on %s (%s)',
            $this->wrap($command->index),
            $this->wrapTable($blueprint),
            $this->columnize($command->columns)
        );
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
	 * 编译删除表命令(是否存在)
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileDropIfExists(Blueprint $blueprint, Fluent $command)
    {
        return sprintf('if exists (select * from INFORMATION_SCHEMA.TABLES where TABLE_NAME = %s) drop table %s',
            "'".str_replace("'", "''", $this->getTablePrefix().$blueprint->getTable())."'",
            $this->wrapTable($blueprint)
        );
    }

    /**
     * Compile the SQL needed to drop all tables.
	 * 编译删除所有表所需的SQL
     *
     * @return string
     */
    public function compileDropAllTables()
    {
        return "EXEC sp_msforeachtable 'DROP TABLE ?'";
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
        $columns = $this->wrapArray($command->columns);

        $dropExistingConstraintsSql = $this->compileDropDefaultConstraint($blueprint, $command).';';

        return $dropExistingConstraintsSql.'alter table '.$this->wrapTable($blueprint).' drop column '.implode(', ', $columns);
    }

    /**
     * Compile a drop default constraint command.
	 * 编译删除默认约束命令
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileDropDefaultConstraint(Blueprint $blueprint, Fluent $command)
    {
        $columns = "'".implode("','", $command->columns)."'";

        $tableName = $this->getTablePrefix().$blueprint->getTable();

        $sql = "DECLARE @sql NVARCHAR(MAX) = '';";
        $sql .= "SELECT @sql += 'ALTER TABLE [dbo].[{$tableName}] DROP CONSTRAINT ' + OBJECT_NAME([default_object_id]) + ';' ";
        $sql .= 'FROM sys.columns ';
        $sql .= "WHERE [object_id] = OBJECT_ID('[dbo].[{$tableName}]') AND [name] in ({$columns}) AND [default_object_id] <> 0;";
        $sql .= 'EXEC(@sql)';

        return $sql;
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
        $index = $this->wrap($command->index);

        return "alter table {$this->wrapTable($blueprint)} drop constraint {$index}";
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

        return "drop index {$index} on {$this->wrapTable($blueprint)}";
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
        $index = $this->wrap($command->index);

        return "drop index {$index} on {$this->wrapTable($blueprint)}";
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

        return "sp_rename {$from}, ".$this->wrapTable($command->to);
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
        return sprintf("sp_rename N'%s', %s, N'INDEX'",
            $this->wrap($blueprint->getTable().'.'.$command->from),
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
        return 'EXEC sp_msforeachtable @command1="print \'?\'", @command2="ALTER TABLE ? WITH CHECK CHECK CONSTRAINT all";';
    }

    /**
     * Compile the command to disable foreign key constraints.
	 * 编译命令以禁用外键约束
     *
     * @return string
     */
    public function compileDisableForeignKeyConstraints()
    {
        return 'EXEC sp_msforeachtable "ALTER TABLE ? NOCHECK CONSTRAINT all";';
    }

    /**
     * Compile the command to drop all foreign keys.
	 * 编译命令以删除所有外键
     *
     * @return string
     */
    public function compileDropAllForeignKeys()
    {
        return "DECLARE @sql NVARCHAR(MAX) = N'';
            SELECT @sql += 'ALTER TABLE '
                + QUOTENAME(OBJECT_SCHEMA_NAME(parent_object_id)) + '.' + + QUOTENAME(OBJECT_NAME(parent_object_id))
                + ' DROP CONSTRAINT ' + QUOTENAME(name) + ';'
            FROM sys.foreign_keys;

            EXEC sp_executesql @sql;";
    }

    /**
     * Compile the command to drop all views.
	 * 编译命令以删除所有视图
     *
     * @return string
     */
    public function compileDropAllViews()
    {
        return "DECLARE @sql NVARCHAR(MAX) = N'';
            SELECT @sql += 'DROP VIEW ' + QUOTENAME(OBJECT_SCHEMA_NAME(object_id)) + '.' + QUOTENAME(name) + ';'
            FROM sys.views;

            EXEC sp_executesql @sql;";
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
        return "nchar({$column->length})";
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
        return "nvarchar({$column->length})";
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
        return 'nvarchar(max)';
    }

    /**
     * Create the column definition for a medium text type.
	 * 创建列定义为中等文本类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeMediumText(Fluent $column)
    {
        return 'nvarchar(max)';
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
        return 'nvarchar(max)';
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
        return 'int';
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
        return 'bigint';
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
        return 'int';
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
        return 'tinyint';
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
        return 'smallint';
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
        return 'float';
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
        return 'float';
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
        return 'bit';
    }

    /**
     * Create the column definition for an enumeration type.
	 * 创建列定义为列举类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeEnum(Fluent $column)
    {
        return sprintf(
            'nvarchar(255) check ("%s" in (%s))',
            $column->name,
            $this->quoteString($column->allowed)
        );
    }

    /**
     * Create the column definition for a json type.
	 * 创建列定义为JSON类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeJson(Fluent $column)
    {
        return 'nvarchar(max)';
    }

    /**
     * Create the column definition for a jsonb type.
	 * 创建列定义为JSONB类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeJsonb(Fluent $column)
    {
        return 'nvarchar(max)';
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
	 * 创建列定义为日期时间类型
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
	 * 创建列定义为时间类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeTime(Fluent $column)
    {
        return $column->precision ? "time($column->precision)" : 'time';
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
        return $this->typeTime($column);
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
        $columnType = $column->precision ? "datetime2($column->precision)" : 'datetime';

        return $column->useCurrent ? "$columnType default CURRENT_TIMESTAMP" : $columnType;
    }

    /**
     * Create the column definition for a timestamp (with time zone) type.
	 * 创建列定义为时间戳(带时区)类型
     *
     * @link https://docs.microsoft.com/en-us/sql/t-sql/data-types/datetimeoffset-transact-sql?view=sql-server-ver15
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeTimestampTz(Fluent $column)
    {
        $columnType = $column->precision ? "datetimeoffset($column->precision)" : 'datetimeoffset';

        return $column->useCurrent ? "$columnType default CURRENT_TIMESTAMP" : $columnType;
    }

    /**
     * Create the column definition for a year type.
	 * 创建列定义为年类型
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
        return 'varbinary(max)';
    }

    /**
     * Create the column definition for a uuid type.
	 * 创建列定义为UUID类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeUuid(Fluent $column)
    {
        return 'uniqueidentifier';
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
        return 'nvarchar(45)';
    }

    /**
     * Create the column definition for a MAC address type.
	 * 创建列定义为MAC地址类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeMacAddress(Fluent $column)
    {
        return 'nvarchar(17)';
    }

    /**
     * Create the column definition for a spatial Geometry type.
	 * 创建列定义为空间几何类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    public function typeGeometry(Fluent $column)
    {
        return 'geography';
    }

    /**
     * Create the column definition for a spatial Point type.
	 * 创建列定义为空间点类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    public function typePoint(Fluent $column)
    {
        return 'geography';
    }

    /**
     * Create the column definition for a spatial LineString type.
	 * 创建列定义为空间LineString类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    public function typeLineString(Fluent $column)
    {
        return 'geography';
    }

    /**
     * Create the column definition for a spatial Polygon type.
	 * 创建列定义为空间多边形类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    public function typePolygon(Fluent $column)
    {
        return 'geography';
    }

    /**
     * Create the column definition for a spatial GeometryCollection type.
	 * 创建列定义为空间GeometryCollection类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    public function typeGeometryCollection(Fluent $column)
    {
        return 'geography';
    }

    /**
     * Create the column definition for a spatial MultiPoint type.
	 * 创建列定义为空间多点类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    public function typeMultiPoint(Fluent $column)
    {
        return 'geography';
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
        return 'geography';
    }

    /**
     * Create the column definition for a spatial MultiPolygon type.
	 * 创建列定义为空间MultiPolygon类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    public function typeMultiPolygon(Fluent $column)
    {
        return 'geography';
    }

    /**
     * Create the column definition for a generated, computed column type.
	 * 创建列定义为生成的、计算的列类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string|null
     */
    protected function typeComputed(Fluent $column)
    {
        return "as ({$column->expression})";
    }

    /**
     * Get the SQL for a collation column modifier.
	 * 得到排序列修饰符的SQL
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $column
     * @return string|null
     */
    protected function modifyCollate(Blueprint $blueprint, Fluent $column)
    {
        if (! is_null($column->collation)) {
            return ' collate '.$column->collation;
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
        if ($column->type !== 'computed') {
            return $column->nullable ? ' null' : ' not null';
        }
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
        if (in_array($column->type, $this->serials) && $column->autoIncrement) {
            return ' identity primary key';
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
    protected function modifyPersisted(Blueprint $blueprint, Fluent $column)
    {
        if ($column->persisted) {
            return ' persisted';
        }
    }

    /**
     * Wrap a table in keyword identifiers.
	 * 包装表用关键字标识符
     *
     * @param  \Illuminate\Database\Query\Expression|string  $table
     * @return string
     */
    public function wrapTable($table)
    {
        if ($table instanceof Blueprint && $table->temporary) {
            $this->setTablePrefix('#');
        }

        return parent::wrapTable($table);
    }

    /**
     * Quote the given string literal.
	 * 引用给定的字符串字面值
     *
     * @param  string|array  $value
     * @return string
     */
    public function quoteString($value)
    {
        if (is_array($value)) {
            return implode(', ', array_map([$this, __FUNCTION__], $value));
        }

        return "N'$value'";
    }
}
