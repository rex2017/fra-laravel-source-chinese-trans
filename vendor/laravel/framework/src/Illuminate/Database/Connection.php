<?php
/**
 * 数据库连接，负责执行SQL查询，与db门面对应
 */

namespace Illuminate\Database;

use Closure;
use DateTimeInterface;
use Doctrine\DBAL\Connection as DoctrineConnection;
use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Events\StatementPrepared;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Database\Events\TransactionRolledBack;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\Grammars\Grammar as QueryGrammar;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Illuminate\Support\Arr;
use LogicException;
use PDO;
use PDOStatement;

class Connection implements ConnectionInterface
{
    use DetectsConcurrencyErrors,
        DetectsLostConnections,
        Concerns\ManagesTransactions;

    /**
     * The active PDO connection.
	 * PDO连接
     *
     * @var \PDO|\Closure
     */
    protected $pdo;

    /**
     * The active PDO connection used for reads.
	 * 读PDO连接
     *
     * @var \PDO|\Closure
     */
    protected $readPdo;

    /**
     * The name of the connected database.
	 * 数据库已连接
     *
     * @var string
     */
    protected $database;

    /**
     * The table prefix for the connection.
	 * 表前缀
     *
     * @var string
     */
    protected $tablePrefix = '';

    /**
     * The database connection configuration options.
	 * 数据库连接配置参数
     *
     * @var array
     */
    protected $config = [];

    /**
     * The reconnector instance for the connection.
	 * 重连接接口
     *
     * @var callable
     */
    protected $reconnector;

    /**
     * The query grammar implementation.
	 * 查询语法实现
     *
     * @var \Illuminate\Database\Query\Grammars\Grammar
     */
    protected $queryGrammar;

    /**
     * The schema grammar implementation.
	 * 模式语法实现
     *
     * @var \Illuminate\Database\Schema\Grammars\Grammar
     */
    protected $schemaGrammar;

    /**
     * The query post processor implementation.
	 * 查询处理器实现
     *
     * @var \Illuminate\Database\Query\Processors\Processor
     */
    protected $postProcessor;

    /**
     * The event dispatcher instance.
	 * 事件调度实例
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * The default fetch mode of the connection.
	 * 默认fetch模式
     *
     * @var int
     */
    protected $fetchMode = PDO::FETCH_OBJ;

    /**
     * The number of active transactions.
	 * 活动数
     *
     * @var int
     */
    protected $transactions = 0;

    /**
     * Indicates if changes have been made to the database.
	 * 指明是否对数据库进行了更改
     *
     * @var int
     */
    protected $recordsModified = false;

    /**
     * All of the queries run against the connection.
	 * 查询日志
     *
     * @var array
     */
    protected $queryLog = [];

    /**
     * Indicates whether queries are being logged.
	 * 指明查询是否被记录
     *
     * @var bool
     */
    protected $loggingQueries = false;

    /**
     * Indicates if the connection is in a "dry run".
	 * 指明连接是否处于"试运行"状态
     *
     * @var bool
     */
    protected $pretending = false;

    /**
     * The instance of Doctrine connection.
	 * 正式连接实例
     *
     * @var \Doctrine\DBAL\Connection
     */
    protected $doctrineConnection;

    /**
     * The connection resolvers.
	 * 连接解析器
     *
     * @var array
     */
    protected static $resolvers = [];

    /**
     * Create a new database connection instance.
	 * 创建新的数据库连接实例
     *
     * @param  \PDO|\Closure  $pdo
     * @param  string  $database
     * @param  string  $tablePrefix
     * @param  array  $config
     * @return void
     */
    public function __construct($pdo, $database = '', $tablePrefix = '', array $config = [])
    {
        $this->pdo = $pdo;

        // First we will setup the default properties. We keep track of the DB
        // name we are connected to since it is needed when some reflective
        // type commands are run such as checking whether a table exists.
		// 首先我们行设置默认属性。
		// 我们将跟踪DB是因为一些反思运行类型命令的需要，例如检查表是否存。
        $this->database = $database;

        $this->tablePrefix = $tablePrefix;

        $this->config = $config;

        // We need to initialize a query grammar and the query post processors
        // which are both very important parts of the database abstractions
        // so we initialize these to their default values while starting.
		// 我们需要初始化查询语法和查询后处理，它们都是数据库抽象的重要组成部分。
		// 因此，我们在启动时将其初始化为默认值。
        $this->useDefaultQueryGrammar();

        $this->useDefaultPostProcessor();
    }

    /**
     * Set the query grammar to the default implementation.
	 * 设置查询语法至默认实现
     *
     * @return void
     */
    public function useDefaultQueryGrammar()
    {
        $this->queryGrammar = $this->getDefaultQueryGrammar();
    }

    /**
     * Get the default query grammar instance.
	 * 得到默认查询语法实例
     *
     * @return \Illuminate\Database\Query\Grammars\Grammar
     */
    protected function getDefaultQueryGrammar()
    {
        return new QueryGrammar;
    }

    /**
     * Set the schema grammar to the default implementation.
	 * 将模式语法设置为默认实现
     *
     * @return void
     */
    public function useDefaultSchemaGrammar()
    {
        $this->schemaGrammar = $this->getDefaultSchemaGrammar();
    }

    /**
     * Get the default schema grammar instance.
	 * 得到默认模式语法实例
     *
     * @return \Illuminate\Database\Schema\Grammars\Grammar
     */
    protected function getDefaultSchemaGrammar()
    {
        //
    }

    /**
     * Set the query post processor to the default implementation.
	 * 设置查询请求程序为默认实现
     *
     * @return void
     */
    public function useDefaultPostProcessor()
    {
        $this->postProcessor = $this->getDefaultPostProcessor();
    }

    /**
     * Get the default post processor instance.
	 * 得到默认请求进程实例
     *
     * @return \Illuminate\Database\Query\Processors\Processor
     */
    protected function getDefaultPostProcessor()
    {
        return new Processor;
    }

    /**
     * Get a schema builder instance for the connection.
	 * 得到连接的架构生成器实例
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    public function getSchemaBuilder()
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new SchemaBuilder($this);
    }

    /**
     * Begin a fluent query against a database table.
	 * 开始对数据表进行流畅的查询
     *
     * @param  \Closure|\Illuminate\Database\Query\Builder|string  $table
     * @param  string|null  $as
     * @return \Illuminate\Database\Query\Builder
     */
    public function table($table, $as = null)
    {
        return $this->query()->from($table, $as);
    }

    /**
     * Get a new query builder instance.
	 * 得到一个新的查询实例
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function query()
    {
        return new QueryBuilder(
            $this, $this->getQueryGrammar(), $this->getPostProcessor()
        );
    }

    /**
     * Run a select statement and return a single result.
	 * 运行一个查询语句并返回单个结果
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  bool  $useReadPdo
     * @return mixed
     */
    public function selectOne($query, $bindings = [], $useReadPdo = true)
    {
        $records = $this->select($query, $bindings, $useReadPdo);

        return array_shift($records);
    }

    /**
     * Run a select statement against the database.
	 * 运行一条查询语句
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return array
     */
    public function selectFromWriteConnection($query, $bindings = [])
    {
        return $this->select($query, $bindings, false);
    }

    /**
     * Run a select statement against the database.
	 * 运行一条select查询语句
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  bool  $useReadPdo
     * @return array
     */
    public function select($query, $bindings = [], $useReadPdo = true)
    {
        return $this->run($query, $bindings, function ($query, $bindings) use ($useReadPdo) {
            if ($this->pretending()) {
                return [];
            }

            // For select statements, we'll simply execute the query and return an array
            // of the database result set. Each element in the array will be a single
            // row from the database table, and will either be an array or objects.
			// 对于select语句，我们只需执行查询并返回数组。
			// 数组中每个元素是数据库表的一行，可以是数据或对象。
            $statement = $this->prepared($this->getPdoForSelect($useReadPdo)
                              ->prepare($query));

            $this->bindValues($statement, $this->prepareBindings($bindings));

            $statement->execute();

            return $statement->fetchAll();
        });
    }

    /**
     * Run a select statement against the database and returns a generator.
	 * 运行select语句并返回生成器
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  bool  $useReadPdo
     * @return \Generator
     */
    public function cursor($query, $bindings = [], $useReadPdo = true)
    {
        $statement = $this->run($query, $bindings, function ($query, $bindings) use ($useReadPdo) {
            if ($this->pretending()) {
                return [];
            }

            // First we will create a statement for the query. Then, we will set the fetch
            // mode and prepare the bindings for the query. Once that's done we will be
            // ready to execute the query against the database and return the cursor.
			// 首先我们将为查询创建一个语句。然后我们将设置取数模式并为查询准备绑定。
			// 一旦完成，我们将对数据库执行查询并返回光标。
            $statement = $this->prepared($this->getPdoForSelect($useReadPdo)
                              ->prepare($query));

            $this->bindValues(
                $statement, $this->prepareBindings($bindings)
            );

            // Next, we'll execute the query against the database and return the statement
            // so we can return the cursor. The cursor will use a PHP generator to give
            // back one row at a time without using a bunch of memory to render them.
			// 接下来，我们将对数据库执行查询并返回以下语句，所以我们可以返回光标。
			// 游标将使用PHP生成器给出一次返回一行，而不使用大量内存来渲染它们。
            $statement->execute();

            return $statement;
        });

        while ($record = $statement->fetch()) {
            yield $record;
        }
    }

    /**
     * Configure the PDO prepared statement.
	 * 配置PDO准备语句
     *
     * @param  \PDOStatement  $statement
     * @return \PDOStatement
     */
    protected function prepared(PDOStatement $statement)
    {
        $statement->setFetchMode($this->fetchMode);

        $this->event(new StatementPrepared(
            $this, $statement
        ));

        return $statement;
    }

    /**
     * Get the PDO connection to use for a select query.
	 * 得到用于选择查询的PDO slelect查询连接
     *
     * @param  bool  $useReadPdo
     * @return \PDO
     */
    protected function getPdoForSelect($useReadPdo = true)
    {
        return $useReadPdo ? $this->getReadPdo() : $this->getPdo();
    }

    /**
     * Run an insert statement against the database.
	 * 运行一条insert插入语句
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return bool
     */
    public function insert($query, $bindings = [])
    {
        return $this->statement($query, $bindings);
    }

    /**
     * Run an update statement against the database.
	 * 运行一条更新语句
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return int
     */
    public function update($query, $bindings = [])
    {
        return $this->affectingStatement($query, $bindings);
    }

    /**
     * Run a delete statement against the database.
	 * 运行一条delete删除语句
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return int
     */
    public function delete($query, $bindings = [])
    {
        return $this->affectingStatement($query, $bindings);
    }

    /**
     * Execute an SQL statement and return the boolean result.
	 * 执行一条SQL语句并返回布尔值
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return bool
     */
    public function statement($query, $bindings = [])
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {
                return true;
            }

            $statement = $this->getPdo()->prepare($query);

            $this->bindValues($statement, $this->prepareBindings($bindings));

            $this->recordsHaveBeenModified();

            return $statement->execute();
        });
    }

    /**
     * Run an SQL statement and get the number of rows affected.
	 * 执行SQL语句并得到影响行
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return int
     */
    public function affectingStatement($query, $bindings = [])
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {
                return 0;
            }

            // For update or delete statements, we want to get the number of rows affected
            // by the statement and return that back to the developer. We'll first need
            // to execute the statement and then we'll use PDO to fetch the affected.
			// 对于update或delete语句，我们希望获得受影响的行数并将其返回给开发人员。
			// 我们首先需要执行该语句，然后我们将使用PDO获取受影响的对象。
            $statement = $this->getPdo()->prepare($query);

            $this->bindValues($statement, $this->prepareBindings($bindings));

            $statement->execute();

            $this->recordsHaveBeenModified(
                ($count = $statement->rowCount()) > 0
            );

            return $count;
        });
    }

    /**
     * Run a raw, unprepared query against the PDO connection.
	 * 运行一个原始的未准备好的查询对PDO
     *
     * @param  string  $query
     * @return bool
     */
    public function unprepared($query)
    {
        return $this->run($query, [], function ($query) {
            if ($this->pretending()) {
                return true;
            }

            $this->recordsHaveBeenModified(
                $change = $this->getPdo()->exec($query) !== false
            );

            return $change;
        });
    }

    /**
     * Execute the given callback in "dry run" mode.
	 * 执行"模拟运行"模式并给定回调
     *
     * @param  \Closure  $callback
     * @return array
     */
    public function pretend(Closure $callback)
    {
        return $this->withFreshQueryLog(function () use ($callback) {
            $this->pretending = true;

            // Basically to make the database connection "pretend", we will just return
            // the default values for all the query methods, then we will return an
            // array of queries that were "executed" within the Closure callback.
			// 基本上为了使数据库连接假装，我们只需返回所有查询方法的默认值，
			// 然后我们将返回在Colsure回调中执行的查询数组。
            $callback($this);

            $this->pretending = false;

            return $this->queryLog;
        });
    }

    /**
     * Execute the given callback in "dry run" mode.
	 * 执行给定的回调在"模拟运行"模式下
     *
     * @param  \Closure  $callback
     * @return array
     */
    protected function withFreshQueryLog($callback)
    {
        $loggingQueries = $this->loggingQueries;

        // First we will back up the value of the logging queries property and then
        // we'll be ready to run callbacks. This query log will also get cleared
        // so we will have a new log of all the queries that are executed now.
		// 首先我们将备份日志查询属性的值，然后我们将准备好运行回调。
		// 此查询日志也将被清除，因此，我们将有一个现在执行的所有查询的新日志。
        $this->enableQueryLog();

        $this->queryLog = [];

        // Now we'll execute this callback and capture the result. Once it has been
        // executed we will restore the value of query logging and give back the
        // value of the callback so the original callers can have the results.
		// 现在我们将执行些回调并捕获结果。
		// 一旦它已经执行后，我们将恢复查询日志记录的值并返回回调的值，以便原始调用者可以获得结果。
        $result = $callback();

        $this->loggingQueries = $loggingQueries;

        return $result;
    }

    /**
     * Bind values to their parameters in the given statement.
	 * 绑定值到给定语句中的参数
     *
     * @param  \PDOStatement  $statement
     * @param  array  $bindings
     * @return void
     */
    public function bindValues($statement, $bindings)
    {
        foreach ($bindings as $key => $value) {
            $statement->bindValue(
                is_string($key) ? $key : $key + 1, $value,
                is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR
            );
        }
    }

    /**
     * Prepare the query bindings for execution.
	 * 准备执行查询绑定
     *
     * @param  array  $bindings
     * @return array
     */
    public function prepareBindings(array $bindings)
    {
        $grammar = $this->getQueryGrammar();

        foreach ($bindings as $key => $value) {
            // We need to transform all instances of DateTimeInterface into the actual
            // date string. Each query grammar maintains its own date string format
            // so we'll just ask the grammar for the format to get from the date.
            if ($value instanceof DateTimeInterface) {
                $bindings[$key] = $value->format($grammar->getDateFormat());
            } elseif (is_bool($value)) {
                $bindings[$key] = (int) $value;
            }
        }

        return $bindings;
    }

    /**
     * Run a SQL statement and log its execution context.
	 * 运行SQL语句并记录其执行上下文
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  \Closure  $callback
     * @return mixed
     *
     * @throws \Illuminate\Database\QueryException
     */
    protected function run($query, $bindings, Closure $callback)
    {
        $this->reconnectIfMissingConnection();

        $start = microtime(true);

        // Here we will run this query. If an exception occurs we'll determine if it was
        // caused by a connection that has been lost. If that is the cause, we'll try
        // to re-establish connection and re-run the query with a fresh connection.
		// 在这里我们将运行此查询。如果发生异常我们将确定是否这是由于连接丢失造成的。
		// 如果这就是原因，我们会试试以重新建立连接，并使用新的连接重新运行查询。
        try {
            $result = $this->runQueryCallback($query, $bindings, $callback);
        } catch (QueryException $e) {
            $result = $this->handleQueryException(
                $e, $query, $bindings, $callback
            );
        }

        // Once we have run the query we will calculate the time that it took to run and
        // then log the query, bindings, and execution time so we will report them on
        // the event that the developer needs them. We'll log time in milliseconds.
		// 一旦运行查询后我们将计算运行所需的时间。
		// 然后记录查询、绑定和执行时间，以便我们报告它们开发人员需要它们的事件。
        $this->logQuery(
            $query, $bindings, $this->getElapsedTime($start)
        );

        return $result;
    }

    /**
     * Run a SQL statement.
	 * 运行SQL语句
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  \Closure  $callback
     * @return mixed
     *
     * @throws \Illuminate\Database\QueryException
     */
    protected function runQueryCallback($query, $bindings, Closure $callback)
    {
        // To execute the statement, we'll simply call the callback, which will actually
        // run the SQL against the PDO connection. Then we can calculate the time it
        // took to execute and log the query SQL, bindings and time in our memory.
        try {
            $result = $callback($query, $bindings);
        }

        // If an exception occurs when attempting to run a query, we'll format the error
        // message to include the bindings with SQL, which will make this exception a
        // lot more helpful to the developer instead of just the database's errors.
        catch (Exception $e) {
            throw new QueryException(
                $query, $this->prepareBindings($bindings), $e
            );
        }

        return $result;
    }

    /**
     * Log a query in the connection's query log.
	 * 日志查询
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  float|null  $time
     * @return void
     */
    public function logQuery($query, $bindings, $time = null)
    {
        $this->event(new QueryExecuted($query, $bindings, $time, $this));

        if ($this->loggingQueries) {
            $this->queryLog[] = compact('query', 'bindings', 'time');
        }
    }

    /**
     * Get the elapsed time since a given starting point.
	 * 得到自给定起始点以来经过的时间
     *
     * @param  int  $start
     * @return float
     */
    protected function getElapsedTime($start)
    {
        return round((microtime(true) - $start) * 1000, 2);
    }

    /**
     * Handle a query exception.
	 * 处理查询异常
     *
     * @param  \Illuminate\Database\QueryException  $e
     * @param  string  $query
     * @param  array  $bindings
     * @param  \Closure  $callback
     * @return mixed
     *
     * @throws \Illuminate\Database\QueryException
     */
    protected function handleQueryException(QueryException $e, $query, $bindings, Closure $callback)
    {
        if ($this->transactions >= 1) {
            throw $e;
        }

        return $this->tryAgainIfCausedByLostConnection(
            $e, $query, $bindings, $callback
        );
    }

    /**
     * Handle a query exception that occurred during query execution.
	 * 处理查询执行期间发生的查询异常
     *
     * @param  \Illuminate\Database\QueryException  $e
     * @param  string  $query
     * @param  array  $bindings
     * @param  \Closure  $callback
     * @return mixed
     *
     * @throws \Illuminate\Database\QueryException
     */
    protected function tryAgainIfCausedByLostConnection(QueryException $e, $query, $bindings, Closure $callback)
    {
        if ($this->causedByLostConnection($e->getPrevious())) {
            $this->reconnect();

            return $this->runQueryCallback($query, $bindings, $callback);
        }

        throw $e;
    }

    /**
     * Reconnect to the database.
	 * 重新连接数据库
     *
     * @return void
     *
     * @throws \LogicException
     */
    public function reconnect()
    {
        if (is_callable($this->reconnector)) {
            $this->doctrineConnection = null;

            return call_user_func($this->reconnector, $this);
        }

        throw new LogicException('Lost connection and no reconnector available.');
    }

    /**
     * Reconnect to the database if a PDO connection is missing.
	 * 请重新连接数据库如果缺少PDO连接，
     *
     * @return void
     */
    protected function reconnectIfMissingConnection()
    {
        if (is_null($this->pdo)) {
            $this->reconnect();
        }
    }

    /**
     * Disconnect from the underlying PDO connection.
	 * 断开底层连接
     *
     * @return void
     */
    public function disconnect()
    {
        $this->setPdo(null)->setReadPdo(null);
    }

    /**
     * Register a database query listener with the connection.
	 * 注册一个数据库查询监听
     *
     * @param  \Closure  $callback
     * @return void
     */
    public function listen(Closure $callback)
    {
        if (isset($this->events)) {
            $this->events->listen(Events\QueryExecuted::class, $callback);
        }
    }

    /**
     * Fire an event for this connection.
	 * 为此连接触发一个事件
     *
     * @param  string  $event
     * @return array|null
     */
    protected function fireConnectionEvent($event)
    {
        if (! isset($this->events)) {
            return;
        }

        switch ($event) {
            case 'beganTransaction':
                return $this->events->dispatch(new TransactionBeginning($this));
            case 'committed':
                return $this->events->dispatch(new TransactionCommitted($this));
            case 'rollingBack':
                return $this->events->dispatch(new TransactionRolledBack($this));
        }
    }

    /**
     * Fire the given event if possible.
	 * 触发事件
     *
     * @param  mixed  $event
     * @return void
     */
    protected function event($event)
    {
        if (isset($this->events)) {
            $this->events->dispatch($event);
        }
    }

    /**
     * Get a new raw query expression.
	 * 得到新的原始查询表达式
     *
     * @param  mixed  $value
     * @return \Illuminate\Database\Query\Expression
     */
    public function raw($value)
    {
        return new Expression($value);
    }

    /**
     * Indicate if any records have been modified.
	 * 说明是否有任何记录被修改
     *
     * @param  bool  $value
     * @return void
     */
    public function recordsHaveBeenModified($value = true)
    {
        if (! $this->recordsModified) {
            $this->recordsModified = $value;
        }
    }

    /**
     * Is Doctrine available?
	 * 学说可用？
     *
     * @return bool
     */
    public function isDoctrineAvailable()
    {
        return class_exists('Doctrine\DBAL\Connection');
    }

    /**
     * Get a Doctrine Schema Column instance.
	 * 得到原则列实例
     *
     * @param  string  $table
     * @param  string  $column
     * @return \Doctrine\DBAL\Schema\Column
     */
    public function getDoctrineColumn($table, $column)
    {
        $schema = $this->getDoctrineSchemaManager();

        return $schema->listTableDetails($table)->getColumn($column);
    }

    /**
     * Get the Doctrine DBAL schema manager for the connection.
	 * 得到连接的Doctrine DBAL模式管理器
     *
     * @return \Doctrine\DBAL\Schema\AbstractSchemaManager
     */
    public function getDoctrineSchemaManager()
    {
        return $this->getDoctrineDriver()->getSchemaManager($this->getDoctrineConnection());
    }

    /**
     * Get the Doctrine DBAL database connection instance.
     *
     * @return \Doctrine\DBAL\Connection
     */
    public function getDoctrineConnection()
    {
        if (is_null($this->doctrineConnection)) {
            $driver = $this->getDoctrineDriver();

            $this->doctrineConnection = new DoctrineConnection(array_filter([
                'pdo' => $this->getPdo(),
                'dbname' => $this->getDatabaseName(),
                'driver' => $driver->getName(),
                'serverVersion' => $this->getConfig('server_version'),
            ]), $driver);
        }

        return $this->doctrineConnection;
    }

    /**
     * Get the current PDO connection.
	 * 得到当前PDO连接
     *
     * @return \PDO
     */
    public function getPdo()
    {
        if ($this->pdo instanceof Closure) {
            return $this->pdo = call_user_func($this->pdo);
        }

        return $this->pdo;
    }

    /**
     * Get the current PDO connection parameter without executing any reconnect logic.
	 * 得到当前的PDO连接参数在不执行任何重新连接逻辑的情况下
     *
     * @return \PDO|\Closure|null
     */
    public function getRawPdo()
    {
        return $this->pdo;
    }

    /**
     * Get the current PDO connection used for reading.
	 * 得到用于读取的当前PDO连接
     *
     * @return \PDO
     */
    public function getReadPdo()
    {
        if ($this->transactions > 0) {
            return $this->getPdo();
        }

        if ($this->recordsModified && $this->getConfig('sticky')) {
            return $this->getPdo();
        }

        if ($this->readPdo instanceof Closure) {
            return $this->readPdo = call_user_func($this->readPdo);
        }

        return $this->readPdo ?: $this->getPdo();
    }

    /**
     * Get the current read PDO connection parameter without executing any reconnect logic.
	 * 得到当前读PDO连接参数在不执行任何重连逻辑的情况下
     *
     * @return \PDO|\Closure|null
     */
    public function getRawReadPdo()
    {
        return $this->readPdo;
    }

    /**
     * Set the PDO connection.
	 * 设置PDO连接
     *
     * @param  \PDO|\Closure|null  $pdo
     * @return $this
     */
    public function setPdo($pdo)
    {
        $this->transactions = 0;

        $this->pdo = $pdo;

        return $this;
    }

    /**
     * Set the PDO connection used for reading.
	 * 设置用于读取的PDO连接
     *
     * @param  \PDO|\Closure|null  $pdo
     * @return $this
     */
    public function setReadPdo($pdo)
    {
        $this->readPdo = $pdo;

        return $this;
    }

    /**
     * Set the reconnect instance on the connection.
	 * 设置重新连接实例
     *
     * @param  callable  $reconnector
     * @return $this
     */
    public function setReconnector(callable $reconnector)
    {
        $this->reconnector = $reconnector;

        return $this;
    }

    /**
     * Get the database connection name.
	 * 得到数据库连接名
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->getConfig('name');
    }

    /**
     * Get an option from the configuration options.
	 * 得到配置选项
     *
     * @param  string|null  $option
     * @return mixed
     */
    public function getConfig($option = null)
    {
        return Arr::get($this->config, $option);
    }

    /**
     * Get the PDO driver name.
	 * 得到PDO驱动名
     *
     * @return string
     */
    public function getDriverName()
    {
        return $this->getConfig('driver');
    }

    /**
     * Get the query grammar used by the connection.
	 * 得到查询语法
     *
     * @return \Illuminate\Database\Query\Grammars\Grammar
     */
    public function getQueryGrammar()
    {
        return $this->queryGrammar;
    }

    /**
     * Set the query grammar used by the connection.
	 * 设置连接使用的查询语法
     *
     * @param  \Illuminate\Database\Query\Grammars\Grammar  $grammar
     * @return $this
     */
    public function setQueryGrammar(Query\Grammars\Grammar $grammar)
    {
        $this->queryGrammar = $grammar;

        return $this;
    }

    /**
     * Get the schema grammar used by the connection.
	 * 得到连接使用的模式语法
     *
     * @return \Illuminate\Database\Schema\Grammars\Grammar
     */
    public function getSchemaGrammar()
    {
        return $this->schemaGrammar;
    }

    /**
     * Set the schema grammar used by the connection.
	 * 设置连接使用的模式语法
     *
     * @param  \Illuminate\Database\Schema\Grammars\Grammar  $grammar
     * @return $this
     */
    public function setSchemaGrammar(Schema\Grammars\Grammar $grammar)
    {
        $this->schemaGrammar = $grammar;

        return $this;
    }

    /**
     * Get the query post processor used by the connection.
	 * 得到连接使用的查询处理程序
     *
     * @return \Illuminate\Database\Query\Processors\Processor
     */
    public function getPostProcessor()
    {
        return $this->postProcessor;
    }

    /**
     * Set the query post processor used by the connection.
	 * 设置连接使用的查询处理器
     *
     * @param  \Illuminate\Database\Query\Processors\Processor  $processor
     * @return $this
     */
    public function setPostProcessor(Processor $processor)
    {
        $this->postProcessor = $processor;

        return $this;
    }

    /**
     * Get the event dispatcher used by the connection.
	 * 得到事件调度
     *
     * @return \Illuminate\Contracts\Events\Dispatcher
     */
    public function getEventDispatcher()
    {
        return $this->events;
    }

    /**
     * Set the event dispatcher instance on the connection.
	 * 设置事件调度实例
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return $this
     */
    public function setEventDispatcher(Dispatcher $events)
    {
        $this->events = $events;

        return $this;
    }

    /**
     * Unset the event dispatcher for this connection.
	 * 注销事件调度
     *
     * @return void
     */
    public function unsetEventDispatcher()
    {
        $this->events = null;
    }

    /**
     * Determine if the connection is in a "dry run".
	 * 确定连接是否处于“预演”状态
     *
     * @return bool
     */
    public function pretending()
    {
        return $this->pretending === true;
    }

    /**
     * Get the connection query log.
	 * 得到连接查询日志
     *
     * @return array
     */
    public function getQueryLog()
    {
        return $this->queryLog;
    }

    /**
     * Clear the query log.
	 * 清空查询日志
     *
     * @return void
     */
    public function flushQueryLog()
    {
        $this->queryLog = [];
    }

    /**
     * Enable the query log on the connection.
	 * 启用查询日志
     *
     * @return void
     */
    public function enableQueryLog()
    {
        $this->loggingQueries = true;
    }

    /**
     * Disable the query log on the connection.
	 * 禁用查询日志
     *
     * @return void
     */
    public function disableQueryLog()
    {
        $this->loggingQueries = false;
    }

    /**
     * Determine whether we're logging queries.
	 * 确定是否日志
     *
     * @return bool
     */
    public function logging()
    {
        return $this->loggingQueries;
    }

    /**
     * Get the name of the connected database.
	 * 得到数据库连接名称
     *
     * @return string
     */
    public function getDatabaseName()
    {
        return $this->database;
    }

    /**
     * Set the name of the connected database.
	 * 设置所连接数据库名称
     *
     * @param  string  $database
     * @return $this
     */
    public function setDatabaseName($database)
    {
        $this->database = $database;

        return $this;
    }

    /**
     * Get the table prefix for the connection.
	 * 得到表前缀
     *
     * @return string
     */
    public function getTablePrefix()
    {
        return $this->tablePrefix;
    }

    /**
     * Set the table prefix in use by the connection.
	 * 设置连接使用的表前缀
     *
     * @param  string  $prefix
     * @return $this
     */
    public function setTablePrefix($prefix)
    {
        $this->tablePrefix = $prefix;

        $this->getQueryGrammar()->setTablePrefix($prefix);

        return $this;
    }

    /**
     * Set the table prefix and return the grammar.
	 * 设置表前缀并返回语法
     *
     * @param  \Illuminate\Database\Grammar  $grammar
     * @return \Illuminate\Database\Grammar
     */
    public function withTablePrefix(Grammar $grammar)
    {
        $grammar->setTablePrefix($this->tablePrefix);

        return $grammar;
    }

    /**
     * Register a connection resolver.
	 * 注册连接解析器
     *
     * @param  string  $driver
     * @param  \Closure  $callback
     * @return void
     */
    public static function resolverFor($driver, Closure $callback)
    {
        static::$resolvers[$driver] = $callback;
    }

    /**
     * Get the connection resolver for the given driver.
	 * 得到连接解析器
     *
     * @param  string  $driver
     * @return mixed
     */
    public static function getResolver($driver)
    {
        return static::$resolvers[$driver] ?? null;
    }
}
