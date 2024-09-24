<?php
/**
 * 数据库，蓝图
 */

namespace Illuminate\Database\Schema;

use BadMethodCallException;
use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Grammars\Grammar;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Fluent;
use Illuminate\Support\Traits\Macroable;

class Blueprint
{
    use Macroable;

    /**
     * The table the blueprint describes.
	 * 蓝图描述表
     *
     * @var string
     */
    protected $table;

    /**
     * The prefix of the table.
	 * 表前缀
     *
     * @var string
     */
    protected $prefix;

    /**
     * The columns that should be added to the table.
	 * 应该添加到表中的列
     *
     * @var \Illuminate\Database\Schema\ColumnDefinition[]
     */
    protected $columns = [];

    /**
     * The commands that should be run for the table.
	 * 应该为表运行的命令
     *
     * @var \Illuminate\Support\Fluent[]
     */
    protected $commands = [];

    /**
     * The storage engine that should be used for the table.
	 * 应该用于表的存储引擎
     *
     * @var string
     */
    public $engine;

    /**
     * The default character set that should be used for the table.
	 * 应用于表的默认字符集
     */
    public $charset;

    /**
     * The collation that should be used for the table.
	 * 应用于表的排序规则
     */
    public $collation;

    /**
     * Whether to make the table temporary.
	 * 是否将表临时化
     *
     * @var bool
     */
    public $temporary = false;

    /**
     * Create a new schema blueprint.
	 * 创建新模式蓝图
     *
     * @param  string  $table
     * @param  \Closure|null  $callback
     * @param  string  $prefix
     * @return void
     */
    public function __construct($table, Closure $callback = null, $prefix = '')
    {
        $this->table = $table;
        $this->prefix = $prefix;

        if (! is_null($callback)) {
            $callback($this);
        }
    }

    /**
     * Execute the blueprint against the database.
	 * 执行蓝图针对数据库
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @param  \Illuminate\Database\Schema\Grammars\Grammar  $grammar
     * @return void
     */
    public function build(Connection $connection, Grammar $grammar)
    {
        foreach ($this->toSql($connection, $grammar) as $statement) {
            $connection->statement($statement);
        }
    }

    /**
     * Get the raw SQL statements for the blueprint.
	 * 得到蓝图的原始SQL语句
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @param  \Illuminate\Database\Schema\Grammars\Grammar  $grammar
     * @return array
     */
    public function toSql(Connection $connection, Grammar $grammar)
    {
        $this->addImpliedCommands($grammar);

        $statements = [];

        // Each type of command has a corresponding compiler function on the schema
        // grammar which is used to build the necessary SQL statements to build
        // the blueprint element, so we'll just call that compilers function.
		// 每种类型的命令在模式语法上都有一个相应的编译器函数，
		// 用于构建构建蓝图元素所需的SQL语句，因此我们只需调用该编译器函数。
        $this->ensureCommandsAreValid($connection);

        foreach ($this->commands as $command) {
            $method = 'compile'.ucfirst($command->name);

            if (method_exists($grammar, $method) || $grammar::hasMacro($method)) {
                if (! is_null($sql = $grammar->$method($this, $command, $connection))) {
                    $statements = array_merge($statements, (array) $sql);
                }
            }
        }

        return $statements;
    }

    /**
     * Ensure the commands on the blueprint are valid for the connection type.
	 * 确保蓝图上的命令对连接类型有效
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @return void
     *
     * @throws \BadMethodCallException
     */
    protected function ensureCommandsAreValid(Connection $connection)
    {
        if ($connection instanceof SQLiteConnection) {
            if ($this->commandsNamed(['dropColumn', 'renameColumn'])->count() > 1) {
                throw new BadMethodCallException(
                    "SQLite doesn't support multiple calls to dropColumn / renameColumn in a single modification."
                );
            }

            if ($this->commandsNamed(['dropForeign'])->count() > 0) {
                throw new BadMethodCallException(
                    "SQLite doesn't support dropping foreign keys (you would need to re-create the table)."
                );
            }
        }
    }

    /**
     * Get all of the commands matching the given names.
	 * 得到与给定名称匹配的所有命令
     *
     * @param  array  $names
     * @return \Illuminate\Support\Collection
     */
    protected function commandsNamed(array $names)
    {
        return collect($this->commands)->filter(function ($command) use ($names) {
            return in_array($command->name, $names);
        });
    }

    /**
     * Add the commands that are implied by the blueprint's state.
	 * 添加蓝图状态所暗示的命令
     *
     * @param  \Illuminate\Database\Schema\Grammars\Grammar  $grammar
     * @return void
     */
    protected function addImpliedCommands(Grammar $grammar)
    {
        if (count($this->getAddedColumns()) > 0 && ! $this->creating()) {
            array_unshift($this->commands, $this->createCommand('add'));
        }

        if (count($this->getChangedColumns()) > 0 && ! $this->creating()) {
            array_unshift($this->commands, $this->createCommand('change'));
        }

        $this->addFluentIndexes();

        $this->addFluentCommands($grammar);
    }

    /**
     * Add the index commands fluently specified on columns.
	 * 添加列上指定的索引命令
     *
     * @return void
     */
    protected function addFluentIndexes()
    {
        foreach ($this->columns as $column) {
            foreach (['primary', 'unique', 'index', 'spatialIndex'] as $index) {
                // If the index has been specified on the given column, but is simply equal
                // to "true" (boolean), no name has been specified for this index so the
                // index method can be called without a name and it will generate one.
				// 如果在给定的列上指定了索引，但仅等于"true"（布尔值），则没有为此索引指定名称，
				// 因此可以在没有名称的情况下调用索引方法，它将生成一个名称。
                if ($column->{$index} === true) {
                    $this->{$index}($column->name);
                    $column->{$index} = false;

                    continue 2;
                }

                // If the index has been specified on the given column, and it has a string
                // value, we'll go ahead and call the index method and pass the name for
                // the index since the developer specified the explicit name for this.
				// 如果在给定的列上指定了索引，并且它有一个字符串值，
				// 我们将继续调用索引方法并传递索引的名称，因为开发人员为此指定了显式名称。
                elseif (isset($column->{$index})) {
                    $this->{$index}($column->name, $column->{$index});
                    $column->{$index} = false;

                    continue 2;
                }
            }
        }
    }

    /**
     * Add the fluent commands specified on any columns.
	 * 添加任意列上指定的fluent命令
     *
     * @param  \Illuminate\Database\Schema\Grammars\Grammar  $grammar
     * @return void
     */
    public function addFluentCommands(Grammar $grammar)
    {
        foreach ($this->columns as $column) {
            foreach ($grammar->getFluentCommands() as $commandName) {
                $attributeName = lcfirst($commandName);

                if (! isset($column->{$attributeName})) {
                    continue;
                }

                $value = $column->{$attributeName};

                $this->addCommand(
                    $commandName, compact('value', 'column')
                );
            }
        }
    }

    /**
     * Determine if the blueprint has a create command.
	 * 确定蓝图是否有create命令
     *
     * @return bool
     */
    protected function creating()
    {
        return collect($this->commands)->contains(function ($command) {
            return $command->name === 'create';
        });
    }

    /**
     * Indicate that the table needs to be created.
	 * 指明需要创建表
     *
     * @return \Illuminate\Support\Fluent
     */
    public function create()
    {
        return $this->addCommand('create');
    }

    /**
     * Indicate that the table needs to be temporary.
	 * 表明该表需要是临时的
     *
     * @return void
     */
    public function temporary()
    {
        $this->temporary = true;
    }

    /**
     * Indicate that the table should be dropped.
	 * 指明应该删除表
     *
     * @return \Illuminate\Support\Fluent
     */
    public function drop()
    {
        return $this->addCommand('drop');
    }

    /**
     * Indicate that the table should be dropped if it exists.
	 * 指明应该删除它如果表存在。
     *
     * @return \Illuminate\Support\Fluent
     */
    public function dropIfExists()
    {
        return $this->addCommand('dropIfExists');
    }

    /**
     * Indicate that the given columns should be dropped.
	 * 指明应该删除给定的列
     *
     * @param  array|mixed  $columns
     * @return \Illuminate\Support\Fluent
     */
    public function dropColumn($columns)
    {
        $columns = is_array($columns) ? $columns : func_get_args();

        return $this->addCommand('dropColumn', compact('columns'));
    }

    /**
     * Indicate that the given columns should be renamed.
	 * 指明应该重命名给定的列
     *
     * @param  string  $from
     * @param  string  $to
     * @return \Illuminate\Support\Fluent
     */
    public function renameColumn($from, $to)
    {
        return $this->addCommand('renameColumn', compact('from', 'to'));
    }

    /**
     * Indicate that the given primary key should be dropped.
	 * 指明应该删除给定的主键
     *
     * @param  string|array|null  $index
     * @return \Illuminate\Support\Fluent
     */
    public function dropPrimary($index = null)
    {
        return $this->dropIndexCommand('dropPrimary', 'primary', $index);
    }

    /**
     * Indicate that the given unique key should be dropped.
	 * 指明应该删除给定的唯一键
     *
     * @param  string|array  $index
     * @return \Illuminate\Support\Fluent
     */
    public function dropUnique($index)
    {
        return $this->dropIndexCommand('dropUnique', 'unique', $index);
    }

    /**
     * Indicate that the given index should be dropped.
	 * 指明应该删除给定的索引
     *
     * @param  string|array  $index
     * @return \Illuminate\Support\Fluent
     */
    public function dropIndex($index)
    {
        return $this->dropIndexCommand('dropIndex', 'index', $index);
    }

    /**
     * Indicate that the given spatial index should be dropped.
	 * 指明应该删除给定的空间索引
     *
     * @param  string|array  $index
     * @return \Illuminate\Support\Fluent
     */
    public function dropSpatialIndex($index)
    {
        return $this->dropIndexCommand('dropSpatialIndex', 'spatialIndex', $index);
    }

    /**
     * Indicate that the given foreign key should be dropped.
	 * 指明应该删除给定的外键
     *
     * @param  string|array  $index
     * @return \Illuminate\Support\Fluent
     */
    public function dropForeign($index)
    {
        return $this->dropIndexCommand('dropForeign', 'foreign', $index);
    }

    /**
     * Indicate that the given indexes should be renamed.
	 * 指明应该重命名给定的索引
     *
     * @param  string  $from
     * @param  string  $to
     * @return \Illuminate\Support\Fluent
     */
    public function renameIndex($from, $to)
    {
        return $this->addCommand('renameIndex', compact('from', 'to'));
    }

    /**
     * Indicate that the timestamp columns should be dropped.
	 * 指明应该删除时间戳列
     *
     * @return void
     */
    public function dropTimestamps()
    {
        $this->dropColumn('created_at', 'updated_at');
    }

    /**
     * Indicate that the timestamp columns should be dropped.
	 * 指明应该删除时间戳列
     *
     * @return void
     */
    public function dropTimestampsTz()
    {
        $this->dropTimestamps();
    }

    /**
     * Indicate that the soft delete column should be dropped.
	 * 指明应删除软删除列
     *
     * @param  string  $column
     * @return void
     */
    public function dropSoftDeletes($column = 'deleted_at')
    {
        $this->dropColumn($column);
    }

    /**
     * Indicate that the soft delete column should be dropped.
	 * 指明应删除软删除列
     *
     * @param  string  $column
     * @return void
     */
    public function dropSoftDeletesTz($column = 'deleted_at')
    {
        $this->dropSoftDeletes($column);
    }

    /**
     * Indicate that the remember token column should be dropped.
	 * 指明应该删除记忆令牌列
     *
     * @return void
     */
    public function dropRememberToken()
    {
        $this->dropColumn('remember_token');
    }

    /**
     * Indicate that the polymorphic columns should be dropped.
	 * 指明应该删除多态列
     *
     * @param  string  $name
     * @param  string|null  $indexName
     * @return void
     */
    public function dropMorphs($name, $indexName = null)
    {
        $this->dropIndex($indexName ?: $this->createIndexName('index', ["{$name}_type", "{$name}_id"]));

        $this->dropColumn("{$name}_type", "{$name}_id");
    }

    /**
     * Rename the table to a given name.
	 * 重命名表为给定的名称
     *
     * @param  string  $to
     * @return \Illuminate\Support\Fluent
     */
    public function rename($to)
    {
        return $this->addCommand('rename', compact('to'));
    }

    /**
     * Specify the primary key(s) for the table.
	 * 指定表的主键
     *
     * @param  string|array  $columns
     * @param  string|null  $name
     * @param  string|null  $algorithm
     * @return \Illuminate\Support\Fluent
     */
    public function primary($columns, $name = null, $algorithm = null)
    {
        return $this->indexCommand('primary', $columns, $name, $algorithm);
    }

    /**
     * Specify a unique index for the table.
	 * 指定唯一索引为表
     *
     * @param  string|array  $columns
     * @param  string|null  $name
     * @param  string|null  $algorithm
     * @return \Illuminate\Support\Fluent
     */
    public function unique($columns, $name = null, $algorithm = null)
    {
        return $this->indexCommand('unique', $columns, $name, $algorithm);
    }

    /**
     * Specify an index for the table.
	 * 指定表索引
     *
     * @param  string|array  $columns
     * @param  string|null  $name
     * @param  string|null  $algorithm
     * @return \Illuminate\Support\Fluent
     */
    public function index($columns, $name = null, $algorithm = null)
    {
        return $this->indexCommand('index', $columns, $name, $algorithm);
    }

    /**
     * Specify a spatial index for the table.
	 * 为表指定空间索引
     *
     * @param  string|array  $columns
     * @param  string|null  $name
     * @return \Illuminate\Support\Fluent
     */
    public function spatialIndex($columns, $name = null)
    {
        return $this->indexCommand('spatialIndex', $columns, $name);
    }

    /**
     * Specify a foreign key for the table.
	 * 为表指定一个外键
     *
     * @param  string|array  $columns
     * @param  string|null  $name
     * @return \Illuminate\Support\Fluent|\Illuminate\Database\Schema\ForeignKeyDefinition
     */
    public function foreign($columns, $name = null)
    {
        return $this->indexCommand('foreign', $columns, $name);
    }

    /**
     * Create a new auto-incrementing integer (4-byte) column on the table.
	 * 创建一个新的自动递增的整数(4字节)列在表上
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function increments($column)
    {
        return $this->unsignedInteger($column, true);
    }

    /**
     * Create a new auto-incrementing integer (4-byte) column on the table.
	 * 创建一个新的自动递增的整数(4字节)列在表上
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function integerIncrements($column)
    {
        return $this->unsignedInteger($column, true);
    }

    /**
     * Create a new auto-incrementing tiny integer (1-byte) column on the table.
	 * 在表上创建一个新的自动递增的小整数(1字节)列
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function tinyIncrements($column)
    {
        return $this->unsignedTinyInteger($column, true);
    }

    /**
     * Create a new auto-incrementing small integer (2-byte) column on the table.
	 * 在表上创建一个新的自动递增的小整数(2字节)列
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function smallIncrements($column)
    {
        return $this->unsignedSmallInteger($column, true);
    }

    /**
     * Create a new auto-incrementing medium integer (3-byte) column on the table.
	 * 在表上创建一个新的自动递增的中等整数(3字节)列
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function mediumIncrements($column)
    {
        return $this->unsignedMediumInteger($column, true);
    }

    /**
     * Create a new auto-incrementing big integer (8-byte) column on the table.
	 * 在表上创建一个新的自动递增的大整数(8字节)列
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function bigIncrements($column)
    {
        return $this->unsignedBigInteger($column, true);
    }

    /**
     * Create a new char column on the table.
	 * 在表上创建一个新的字符列
     *
     * @param  string  $column
     * @param  int|null  $length
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function char($column, $length = null)
    {
        $length = $length ?: Builder::$defaultStringLength;

        return $this->addColumn('char', $column, compact('length'));
    }

    /**
     * Create a new string column on the table.
	 * 在表上创建一个新的字符串列
     *
     * @param  string  $column
     * @param  int|null  $length
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function string($column, $length = null)
    {
        $length = $length ?: Builder::$defaultStringLength;

        return $this->addColumn('string', $column, compact('length'));
    }

    /**
     * Create a new text column on the table.
	 * 在表上创建一个新的文本列
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function text($column)
    {
        return $this->addColumn('text', $column);
    }

    /**
     * Create a new medium text column on the table.
	 * 在表上创建一个新的中等文本列
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function mediumText($column)
    {
        return $this->addColumn('mediumText', $column);
    }

    /**
     * Create a new long text column on the table.
	 * 在表上创建一个新的中等文本列
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function longText($column)
    {
        return $this->addColumn('longText', $column);
    }

    /**
     * Create a new integer (4-byte) column on the table.
	 * 在表上创建一个新的整数(4字节)列
	 * 
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     * @param  bool  $unsigned
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function integer($column, $autoIncrement = false, $unsigned = false)
    {
        return $this->addColumn('integer', $column, compact('autoIncrement', 'unsigned'));
    }

    /**
     * Create a new tiny integer (1-byte) column on the table.
	 * 在表上创建一个新的小整数(1字节)列
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     * @param  bool  $unsigned
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function tinyInteger($column, $autoIncrement = false, $unsigned = false)
    {
        return $this->addColumn('tinyInteger', $column, compact('autoIncrement', 'unsigned'));
    }

    /**
     * Create a new small integer (2-byte) column on the table.
	 * 在表上创建一个新的小整数(2字节)列
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     * @param  bool  $unsigned
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function smallInteger($column, $autoIncrement = false, $unsigned = false)
    {
        return $this->addColumn('smallInteger', $column, compact('autoIncrement', 'unsigned'));
    }

    /**
     * Create a new medium integer (3-byte) column on the table.
	 * 在表上创建一个新的中等整数(3字节)列
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     * @param  bool  $unsigned
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function mediumInteger($column, $autoIncrement = false, $unsigned = false)
    {
        return $this->addColumn('mediumInteger', $column, compact('autoIncrement', 'unsigned'));
    }

    /**
     * Create a new big integer (8-byte) column on the table.
	 * 在表上创建一个新的大整数(8字节)列
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     * @param  bool  $unsigned
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function bigInteger($column, $autoIncrement = false, $unsigned = false)
    {
        return $this->addColumn('bigInteger', $column, compact('autoIncrement', 'unsigned'));
    }

    /**
     * Create a new unsigned integer (4-byte) column on the table.
	 * 在表上创建一个新的无符号整数(4字节)列
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function unsignedInteger($column, $autoIncrement = false)
    {
        return $this->integer($column, $autoIncrement, true);
    }

    /**
     * Create a new unsigned tiny integer (1-byte) column on the table.
	 * 在表上创建一个新的无符号小整数(1字节)列
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function unsignedTinyInteger($column, $autoIncrement = false)
    {
        return $this->tinyInteger($column, $autoIncrement, true);
    }

    /**
     * Create a new unsigned small integer (2-byte) column on the table.
	 * 在表上创建一个新的无符号小整数(2字节)列
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function unsignedSmallInteger($column, $autoIncrement = false)
    {
        return $this->smallInteger($column, $autoIncrement, true);
    }

    /**
     * Create a new unsigned medium integer (3-byte) column on the table.
	 * 在表上创建一个新的无符号中整数(3字节)列
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function unsignedMediumInteger($column, $autoIncrement = false)
    {
        return $this->mediumInteger($column, $autoIncrement, true);
    }

    /**
     * Create a new unsigned big integer (8-byte) column on the table.
	 * 在表上创建一个新的无符号大整数(8字节)列
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function unsignedBigInteger($column, $autoIncrement = false)
    {
        return $this->bigInteger($column, $autoIncrement, true);
    }

    /**
     * Create a new float column on the table.
	 * 在表上创建一个新的浮动列
     *
     * @param  string  $column
     * @param  int  $total
     * @param  int  $places
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function float($column, $total = 8, $places = 2)
    {
        return $this->addColumn('float', $column, [
            'total' => $total, 'places' => $places, 'unsigned' => false,
        ]);
    }

    /**
     * Create a new double column on the table.
	 * 在表上创建一个新的双列
     *
     * @param  string  $column
     * @param  int|null  $total
     * @param  int|null  $places
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function double($column, $total = null, $places = null)
    {
        return $this->addColumn('double', $column, [
            'total' => $total, 'places' => $places, 'unsigned' => false,
        ]);
    }

    /**
     * Create a new decimal column on the table.
	 * 在表上创建一个新的十进制列
     *
     * @param  string  $column
     * @param  int  $total
     * @param  int  $places
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function decimal($column, $total = 8, $places = 2)
    {
        return $this->addColumn('decimal', $column, [
            'total' => $total, 'places' => $places, 'unsigned' => false,
        ]);
    }

    /**
     * Create a new unsigned decimal column on the table.
	 * 在表上创建一个新的无符号十进制列
     *
     * @param  string  $column
     * @param  int  $total
     * @param  int  $places
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function unsignedDecimal($column, $total = 8, $places = 2)
    {
        return $this->addColumn('decimal', $column, [
            'total' => $total, 'places' => $places, 'unsigned' => true,
        ]);
    }

    /**
     * Create a new boolean column on the table.
	 * 在表上创建一个新的布尔列
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function boolean($column)
    {
        return $this->addColumn('boolean', $column);
    }

    /**
     * Create a new enum column on the table.
	 * 在表上创建一个新的枚举列
     *
     * @param  string  $column
     * @param  array  $allowed
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function enum($column, array $allowed)
    {
        return $this->addColumn('enum', $column, compact('allowed'));
    }

    /**
     * Create a new set column on the table.
	 * 在表上创建一个新的set列
     *
     * @param  string  $column
     * @param  array  $allowed
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function set($column, array $allowed)
    {
        return $this->addColumn('set', $column, compact('allowed'));
    }

    /**
     * Create a new json column on the table.
	 * 在表上创建一个新的json列
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function json($column)
    {
        return $this->addColumn('json', $column);
    }

    /**
     * Create a new jsonb column on the table.
	 * 在表上创建一个新的jsonb列
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function jsonb($column)
    {
        return $this->addColumn('jsonb', $column);
    }

    /**
     * Create a new date column on the table.
	 * 在表上创建一个新的日期列
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function date($column)
    {
        return $this->addColumn('date', $column);
    }

    /**
     * Create a new date-time column on the table.
	 * 在表上创建一个新的日期-时间列
     *
     * @param  string  $column
     * @param  int  $precision
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function dateTime($column, $precision = 0)
    {
        return $this->addColumn('dateTime', $column, compact('precision'));
    }

    /**
     * Create a new date-time column (with time zone) on the table.
	 * 在表上创建一个新的日期-时间列(带时区)
     *
     * @param  string  $column
     * @param  int  $precision
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function dateTimeTz($column, $precision = 0)
    {
        return $this->addColumn('dateTimeTz', $column, compact('precision'));
    }

    /**
     * Create a new time column on the table.
	 * 在表上创建一个新的时间列
     *
     * @param  string  $column
     * @param  int  $precision
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function time($column, $precision = 0)
    {
        return $this->addColumn('time', $column, compact('precision'));
    }

    /**
     * Create a new time column (with time zone) on the table.
	 * 在表上创建一个新的时间列(带时区)
     *
     * @param  string  $column
     * @param  int  $precision
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function timeTz($column, $precision = 0)
    {
        return $this->addColumn('timeTz', $column, compact('precision'));
    }

    /**
     * Create a new timestamp column on the table.
	 * 在表上创建一个新的时间戳列
     *
     * @param  string  $column
     * @param  int  $precision
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function timestamp($column, $precision = 0)
    {
        return $this->addColumn('timestamp', $column, compact('precision'));
    }

    /**
     * Create a new timestamp (with time zone) column on the table.
	 * 表上创建一个新的时间戳(带时区)列
     *
     * @param  string  $column
     * @param  int  $precision
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function timestampTz($column, $precision = 0)
    {
        return $this->addColumn('timestampTz', $column, compact('precision'));
    }

    /**
     * Add nullable creation and update timestamps to the table.
	 * 向表中添加可空的创建和更新时间戳
     *
     * @param  int  $precision
     * @return void
     */
    public function timestamps($precision = 0)
    {
        $this->timestamp('created_at', $precision)->nullable();

        $this->timestamp('updated_at', $precision)->nullable();
    }

    /**
     * Add nullable creation and update timestamps to the table.
	 * 向表中添加可空的创建和更新时间戳
     *
     * Alias for self::timestamps().
     *
     * @param  int  $precision
     * @return void
     */
    public function nullableTimestamps($precision = 0)
    {
        $this->timestamps($precision);
    }

    /**
     * Add creation and update timestampTz columns to the table.
	 * 向表中添加创建和更新timestampTz列
     *
     * @param  int  $precision
     * @return void
     */
    public function timestampsTz($precision = 0)
    {
        $this->timestampTz('created_at', $precision)->nullable();

        $this->timestampTz('updated_at', $precision)->nullable();
    }

    /**
     * Add a "deleted at" timestamp for the table.
	 * 为表添加一个"deleted at"时间戳
     *
     * @param  string  $column
     * @param  int  $precision
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function softDeletes($column = 'deleted_at', $precision = 0)
    {
        return $this->timestamp($column, $precision)->nullable();
    }

    /**
     * Add a "deleted at" timestampTz for the table.
	 * 为表添加一个"deleted at"时间戳tz
     *
     * @param  string  $column
     * @param  int  $precision
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function softDeletesTz($column = 'deleted_at', $precision = 0)
    {
        return $this->timestampTz($column, $precision)->nullable();
    }

    /**
     * Create a new year column on the table.
	 * 在表上创建新的year列
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function year($column)
    {
        return $this->addColumn('year', $column);
    }

    /**
     * Create a new binary column on the table.
	 * 在表上创建新的二进制列
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function binary($column)
    {
        return $this->addColumn('binary', $column);
    }

    /**
     * Create a new uuid column on the table.
	 * 在表上创建新的uuid列
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function uuid($column)
    {
        return $this->addColumn('uuid', $column);
    }

    /**
     * Create a new IP address column on the table.
	 * 在表上创建新的IP地址列
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function ipAddress($column)
    {
        return $this->addColumn('ipAddress', $column);
    }

    /**
     * Create a new MAC address column on the table.
	 * 在表上创建一个新的MAC地址列
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function macAddress($column)
    {
        return $this->addColumn('macAddress', $column);
    }

    /**
     * Create a new geometry column on the table.
	 * 在表上创建新的几何列
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function geometry($column)
    {
        return $this->addColumn('geometry', $column);
    }

    /**
     * Create a new point column on the table.
	 * 在表上创建新的点列
     *
     * @param  string  $column
     * @param  int|null  $srid
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function point($column, $srid = null)
    {
        return $this->addColumn('point', $column, compact('srid'));
    }

    /**
     * Create a new linestring column on the table.
	 * 在表上创建新的linestring列
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function lineString($column)
    {
        return $this->addColumn('linestring', $column);
    }

    /**
     * Create a new polygon column on the table.
	 * 在表上创建新的多边形列
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function polygon($column)
    {
        return $this->addColumn('polygon', $column);
    }

    /**
     * Create a new geometrycollection column on the table.
	 * 在表上创建新的geometrycollection列
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function geometryCollection($column)
    {
        return $this->addColumn('geometrycollection', $column);
    }

    /**
     * Create a new multipoint column on the table.
	 * 在表上创建新的多点列
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function multiPoint($column)
    {
        return $this->addColumn('multipoint', $column);
    }

    /**
     * Create a new multilinestring column on the table.
	 * 在表上创建新的multilinestring列
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function multiLineString($column)
    {
        return $this->addColumn('multilinestring', $column);
    }

    /**
     * Create a new multipolygon column on the table.
	 * 在表上创建新的多多边形列
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function multiPolygon($column)
    {
        return $this->addColumn('multipolygon', $column);
    }

    /**
     * Create a new multipolygon column on the table.
	 * 在表上创建新的多多边形列
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function multiPolygonZ($column)
    {
        return $this->addColumn('multipolygonz', $column);
    }

    /**
     * Create a new generated, computed column on the table.
	 * 在表上创建新生成的计算列
     *
     * @param  string  $column
     * @param  string  $expression
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function computed($column, $expression)
    {
        return $this->addColumn('computed', $column, compact('expression'));
    }

    /**
     * Add the proper columns for a polymorphic table.
	 * 为多态表添加适当的列
     *
     * @param  string  $name
     * @param  string|null  $indexName
     * @return void
     */
    public function morphs($name, $indexName = null)
    {
        $this->string("{$name}_type");

        $this->unsignedBigInteger("{$name}_id");

        $this->index(["{$name}_type", "{$name}_id"], $indexName);
    }

    /**
     * Add nullable columns for a polymorphic table.
	 * 为多态表添加可空列
     *
     * @param  string  $name
     * @param  string|null  $indexName
     * @return void
     */
    public function nullableMorphs($name, $indexName = null)
    {
        $this->string("{$name}_type")->nullable();

        $this->unsignedBigInteger("{$name}_id")->nullable();

        $this->index(["{$name}_type", "{$name}_id"], $indexName);
    }

    /**
     * Add the proper columns for a polymorphic table using UUIDs.
	 * 为使用uid的多态表添加适当的列
     *
     * @param  string  $name
     * @param  string|null  $indexName
     * @return void
     */
    public function uuidMorphs($name, $indexName = null)
    {
        $this->string("{$name}_type");

        $this->uuid("{$name}_id");

        $this->index(["{$name}_type", "{$name}_id"], $indexName);
    }

    /**
     * Add nullable columns for a polymorphic table using UUIDs.
	 * 为使用uid的多态表添加可空列
     *
     * @param  string  $name
     * @param  string|null  $indexName
     * @return void
     */
    public function nullableUuidMorphs($name, $indexName = null)
    {
        $this->string("{$name}_type")->nullable();

        $this->uuid("{$name}_id")->nullable();

        $this->index(["{$name}_type", "{$name}_id"], $indexName);
    }

    /**
     * Adds the `remember_token` column to the table.
	 * 将'memor_token'列添加到表中
     *
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function rememberToken()
    {
        return $this->string('remember_token', 100)->nullable();
    }

    /**
     * Add a new index command to the blueprint.
	 * 添加新的索引命令至蓝图
     *
     * @param  string  $type
     * @param  string|array  $columns
     * @param  string  $index
     * @param  string|null  $algorithm
     * @return \Illuminate\Support\Fluent
     */
    protected function indexCommand($type, $columns, $index, $algorithm = null)
    {
        $columns = (array) $columns;

        // If no name was specified for this index, we will create one using a basic
        // convention of the table name, followed by the columns, followed by an
        // index type, such as primary or index, which makes the index unique.
		// 如果没有为此索引指定名称，我们将使用表名、列和索引类型（如主索引或索引）
		// 的基本约定创建一个索引，使索引唯一。
        $index = $index ?: $this->createIndexName($type, $columns);

        return $this->addCommand(
            $type, compact('index', 'columns', 'algorithm')
        );
    }

    /**
     * Create a new drop index command on the blueprint.
	 * 创建新的删除索引命令在蓝图上
     *
     * @param  string  $command
     * @param  string  $type
     * @param  string|array  $index
     * @return \Illuminate\Support\Fluent
     */
    protected function dropIndexCommand($command, $type, $index)
    {
        $columns = [];

        // If the given "index" is actually an array of columns, the developer means
        // to drop an index merely by specifying the columns involved without the
        // conventional name, so we will build the index name from the columns.
		// 如果给定的“索引”实际上是一个列数组，开发人员意味着只需指定所涉及的列而不指定常规名称即可删除索引，
		// 因此我们将根据列构建索引名称。
        if (is_array($index)) {
            $index = $this->createIndexName($type, $columns = $index);
        }

        return $this->indexCommand($command, $columns, $index);
    }

    /**
     * Create a default index name for the table.
	 * 创建默认索引名为表
     *
     * @param  string  $type
     * @param  array  $columns
     * @return string
     */
    protected function createIndexName($type, array $columns)
    {
        $index = strtolower($this->prefix.$this->table.'_'.implode('_', $columns).'_'.$type);

        return str_replace(['-', '.'], '_', $index);
    }

    /**
     * Add a new column to the blueprint.
	 * 添加一个新列至蓝图
     *
     * @param  string  $type
     * @param  string  $name
     * @param  array  $parameters
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function addColumn($type, $name, array $parameters = [])
    {
        $this->columns[] = $column = new ColumnDefinition(
            array_merge(compact('type', 'name'), $parameters)
        );

        return $column;
    }

    /**
     * Remove a column from the schema blueprint.
	 * 删除列从架构蓝图中
     *
     * @param  string  $name
     * @return $this
     */
    public function removeColumn($name)
    {
        $this->columns = array_values(array_filter($this->columns, function ($c) use ($name) {
            return $c['name'] != $name;
        }));

        return $this;
    }

    /**
     * Add a new command to the blueprint.
	 * 添加列于蓝图中
     *
     * @param  string  $name
     * @param  array  $parameters
     * @return \Illuminate\Support\Fluent
     */
    protected function addCommand($name, array $parameters = [])
    {
        $this->commands[] = $command = $this->createCommand($name, $parameters);

        return $command;
    }

    /**
     * Create a new Fluent command.
	 * 创建新的Fluent命令
     *
     * @param  string  $name
     * @param  array  $parameters
     * @return \Illuminate\Support\Fluent
     */
    protected function createCommand($name, array $parameters = [])
    {
        return new Fluent(array_merge(compact('name'), $parameters));
    }

    /**
     * Get the table the blueprint describes.
	 * 得到蓝图描述的表
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Get the columns on the blueprint.
	 * 得到蓝图上的列
     *
     * @return \Illuminate\Database\Schema\ColumnDefinition[]
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Get the commands on the blueprint.
	 * 得到蓝图上的命令
     *
     * @return \Illuminate\Support\Fluent[]
     */
    public function getCommands()
    {
        return $this->commands;
    }

    /**
     * Get the columns on the blueprint that should be added.
	 * 得到蓝图上的列应该被添加的
     *
     * @return \Illuminate\Database\Schema\ColumnDefinition[]
     */
    public function getAddedColumns()
    {
        return array_filter($this->columns, function ($column) {
            return ! $column->change;
        });
    }

    /**
     * Get the columns on the blueprint that should be changed.
	 * 得到蓝图上的列应该被改变的
     *
     * @return \Illuminate\Database\Schema\ColumnDefinition[]
     */
    public function getChangedColumns()
    {
        return array_filter($this->columns, function ($column) {
            return (bool) $column->change;
        });
    }
}
