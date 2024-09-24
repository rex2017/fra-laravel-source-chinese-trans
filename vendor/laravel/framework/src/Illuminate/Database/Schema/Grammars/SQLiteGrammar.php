<?php
/**
 * 数据库，SQLite语法
 */

namespace Illuminate\Database\Schema\Grammars;

use Doctrine\DBAL\Schema\Index;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Arr;
use Illuminate\Support\Fluent;
use RuntimeException;

class SQLiteGrammar extends Grammar
{
    /**
     * The possible column modifiers.
	 * 可能的列修饰符
     *
     * @var array
     */
    protected $modifiers = ['Nullable', 'Default', 'Increment'];

    /**
     * The columns available as serials.
	 * 作为序列可用的列
     *
     * @var array
     */
    protected $serials = ['bigInteger', 'integer', 'mediumInteger', 'smallInteger', 'tinyInteger'];

    /**
     * Compile the query to determine if a table exists.
	 * 编译查询以确定表是否存在
     *
     * @return string
     */
    public function compileTableExists()
    {
        return "select * from sqlite_master where type = 'table' and name = ?";
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
        return 'pragma table_info('.$this->wrap(str_replace('.', '__', $table)).')';
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
        return sprintf('%s table %s (%s%s%s)',
            $blueprint->temporary ? 'create temporary' : 'create',
            $this->wrapTable($blueprint),
            implode(', ', $this->getColumns($blueprint)),
            (string) $this->addForeignKeys($blueprint),
            (string) $this->addPrimaryKeys($blueprint)
        );
    }

    /**
     * Get the foreign key syntax for a table creation statement.
	 * 得到表创建语句的外键语法
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @return string|null
     */
    protected function addForeignKeys(Blueprint $blueprint)
    {
        $foreigns = $this->getCommandsByName($blueprint, 'foreign');

        return collect($foreigns)->reduce(function ($sql, $foreign) {
            // Once we have all the foreign key commands for the table creation statement
            // we'll loop through each of them and add them to the create table SQL we
            // are building, since SQLite needs foreign keys on the tables creation.
			// 一旦我们通过每个表创建语句获得了所有外键命令，并将它们添加到我们正在构建的创建表SQL中，
			// 因为SQLite在创建表时需要外键。
            $sql .= $this->getForeignKey($foreign);

            if (! is_null($foreign->onDelete)) {
                $sql .= " on delete {$foreign->onDelete}";
            }

            // If this foreign key specifies the action to be taken on update we will add
            // that to the statement here. We'll append it to this SQL and then return
            // the SQL so we can keep adding any other foreign constraints onto this.
			// 如果此外键指定了更新时要采取的操作，我们将在此处将其添加到语句中。
			// 我们将把它附加到此SQL中，然后返回SQL，这样我们就可以继续向其添加任何其他外部约束
            if (! is_null($foreign->onUpdate)) {
                $sql .= " on update {$foreign->onUpdate}";
            }

            return $sql;
        }, '');
    }

    /**
     * Get the SQL for the foreign key.
	 * 得到外键的SQL
     *
     * @param  \Illuminate\Support\Fluent  $foreign
     * @return string
     */
    protected function getForeignKey($foreign)
    {
        // We need to columnize the columns that the foreign key is being defined for
        // so that it is a properly formatted list. Once we have done this, we can
        // return the foreign key SQL declaration to the calling method for use.
		// 我们需要对定义外键的列进行列化，使其成为格式正确的列表。
		// 完成此操作后，我们可以将外键SQL声明返回给调用方法以供使用。
        return sprintf(', foreign key(%s) references %s(%s)',
            $this->columnize($foreign->columns),
            $this->wrapTable($foreign->on),
            $this->columnize((array) $foreign->references)
        );
    }

    /**
     * Get the primary key syntax for a table creation statement.
	 * 得到表创建语句的主键语法
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @return string|null
     */
    protected function addPrimaryKeys(Blueprint $blueprint)
    {
        if (! is_null($primary = $this->getCommandByName($blueprint, 'primary'))) {
            return ", primary key ({$this->columnize($primary->columns)})";
        }
    }

    /**
     * Compile alter table commands for adding columns.
	 * 编译alter table命令来添加列
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return array
     */
    public function compileAdd(Blueprint $blueprint, Fluent $command)
    {
        $columns = $this->prefixArray('add column', $this->getColumns($blueprint));

        return collect($columns)->map(function ($column) use ($blueprint) {
            return 'alter table '.$this->wrapTable($blueprint).' '.$column;
        })->all();
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
        return sprintf('create unique index %s on %s (%s)',
            $this->wrap($command->index),
            $this->wrapTable($blueprint),
            $this->columnize($command->columns)
        );
    }

    /**
     * Compile a plain index key command.
	 * 编译普通索引键命令
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
	 * 编译空间索引键命令
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return void
     *
     * @throws \RuntimeException
     */
    public function compileSpatialIndex(Blueprint $blueprint, Fluent $command)
    {
        throw new RuntimeException('The database driver in use does not support spatial indexes.');
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
        // Handled on table creation...
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
	 * 编译删除表(如果存在)命令
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
     * @return string
     */
    public function compileDropAllTables()
    {
        return "delete from sqlite_master where type in ('table', 'index', 'trigger')";
    }

    /**
     * Compile the SQL needed to drop all views.
	 * 编译删除所有视图所需的SQL
     *
     * @return string
     */
    public function compileDropAllViews()
    {
        return "delete from sqlite_master where type in ('view')";
    }

    /**
     * Compile the SQL needed to rebuild the database.
	 * 编译重建数据库所需的SQL
     *
     * @return string
     */
    public function compileRebuild()
    {
        return 'vacuum';
    }

    /**
     * Compile a drop column command.
	 * 编译删除列命令
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @param  \Illuminate\Database\Connection  $connection
     * @return array
     */
    public function compileDropColumn(Blueprint $blueprint, Fluent $command, Connection $connection)
    {
        $tableDiff = $this->getDoctrineTableDiff(
            $blueprint, $schema = $connection->getDoctrineSchemaManager()
        );

        foreach ($command->columns as $name) {
            $tableDiff->removedColumns[$name] = $connection->getDoctrineColumn(
                $this->getTablePrefix().$blueprint->getTable(), $name
            );
        }

        return (array) $schema->getDatabasePlatform()->getAlterTableSQL($tableDiff);
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

        return "drop index {$index}";
    }

    /**
     * Compile a drop index command.
	 * 编写删除索引命令
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileDropIndex(Blueprint $blueprint, Fluent $command)
    {
        $index = $this->wrap($command->index);

        return "drop index {$index}";
    }

    /**
     * Compile a drop spatial index command.
	 * 编译删除空间索引命令
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return void
     *
     * @throws \RuntimeException
     */
    public function compileDropSpatialIndex(Blueprint $blueprint, Fluent $command)
    {
        throw new RuntimeException('The database driver in use does not support spatial indexes.');
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
     * @param  \Illuminate\Database\Connection  $connection
     * @return array
     *
     * @throws \RuntimeException
     */
    public function compileRenameIndex(Blueprint $blueprint, Fluent $command, Connection $connection)
    {
        $schemaManager = $connection->getDoctrineSchemaManager();

        $indexes = $schemaManager->listTableIndexes($this->getTablePrefix().$blueprint->getTable());

        $index = Arr::get($indexes, $command->from);

        if (! $index) {
            throw new RuntimeException("Index [{$command->from}] does not exist.");
        }

        $newIndex = new Index(
            $command->to, $index->getColumns(), $index->isUnique(),
            $index->isPrimary(), $index->getFlags(), $index->getOptions()
        );

        $platform = $schemaManager->getDatabasePlatform();

        return [
            $platform->getDropIndexSQL($command->from, $this->getTablePrefix().$blueprint->getTable()),
            $platform->getCreateIndexSQL($newIndex, $this->getTablePrefix().$blueprint->getTable()),
        ];
    }

    /**
     * Compile the command to enable foreign key constraints.
	 * 编译命令以启用外键约束
     *
     * @return string
     */
    public function compileEnableForeignKeyConstraints()
    {
        return 'PRAGMA foreign_keys = ON;';
    }

    /**
     * Compile the command to disable foreign key constraints.
	 * 编译命令以禁用外键约束
     *
     * @return string
     */
    public function compileDisableForeignKeyConstraints()
    {
        return 'PRAGMA foreign_keys = OFF;';
    }

    /**
     * Compile the SQL needed to enable a writable schema.
	 * 编译启用可写模式所需的SQL
     *
     * @return string
     */
    public function compileEnableWriteableSchema()
    {
        return 'PRAGMA writable_schema = 1;';
    }

    /**
     * Compile the SQL needed to disable a writable schema.
	 * 编译禁用可写模式所需的SQL
     *
     * @return string
     */
    public function compileDisableWriteableSchema()
    {
        return 'PRAGMA writable_schema = 0;';
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
        return 'varchar';
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
        return 'varchar';
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
	 * 创建列定义为中等文本类型
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
     * Create the column definition for a integer type.
	 * 创建列定义为整数类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeInteger(Fluent $column)
    {
        return 'integer';
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
        return 'integer';
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
        return 'integer';
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
        return 'integer';
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
        return 'integer';
    }

    /**
     * Create the column definition for a float type.
	 * 为float类型创建列定义
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
        return 'numeric';
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
        return 'tinyint(1)';
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
            'varchar check ("%s" in (%s))',
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
        return 'text';
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
        return 'text';
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
     * Note: "SQLite does not have a storage class set aside for storing dates and/or times."
     *
     * @link https://www.sqlite.org/datatype3.html
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeDateTimeTz(Fluent $column)
    {
        return $this->typeDateTime($column);
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
        return 'time';
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
        return $column->useCurrent ? 'datetime default CURRENT_TIMESTAMP' : 'datetime';
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
        return $this->typeTimestamp($column);
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
        return 'blob';
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
        return 'varchar';
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
        return 'varchar';
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
        return 'varchar';
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
        return 'geometry';
    }

    /**
     * Create the column definition for a spatial Point type.
	 * 创建列定义为空间Point类型
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    public function typePoint(Fluent $column)
    {
        return 'point';
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
        return 'linestring';
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
        return 'polygon';
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
        return 'geometrycollection';
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
        return 'multipoint';
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
        return 'multilinestring';
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
        return 'multipolygon';
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
        if (in_array($column->type, $this->serials) && $column->autoIncrement) {
            return ' primary key autoincrement';
        }
    }
}
