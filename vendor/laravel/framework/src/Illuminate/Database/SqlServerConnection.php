<?php
/**
 * 数据库SqlServer连接
 */

namespace Illuminate\Database;

use Closure;
use Doctrine\DBAL\Driver\PDOSqlsrv\Driver as DoctrineDriver;
use Exception;
use Illuminate\Database\Query\Grammars\SqlServerGrammar as QueryGrammar;
use Illuminate\Database\Query\Processors\SqlServerProcessor;
use Illuminate\Database\Schema\Grammars\SqlServerGrammar as SchemaGrammar;
use Illuminate\Database\Schema\SqlServerBuilder;
use LogicException;
use Throwable;

class SqlServerConnection extends Connection
{
    /**
     * Execute a Closure within a transaction.
	 * 执行闭包使用事务
     *
     * @param  \Closure  $callback
     * @param  int  $attempts
     * @return mixed
     *
     * @throws \Exception|\Throwable
     */
    public function transaction(Closure $callback, $attempts = 1)
    {
        for ($a = 1; $a <= $attempts; $a++) {
            if ($this->getDriverName() === 'sqlsrv') {
                return parent::transaction($callback);
            }

            $this->getPdo()->exec('BEGIN TRAN');

            // We'll simply execute the given callback within a try / catch block
            // and if we catch any exception we can rollback the transaction
            // so that none of the changes are persisted to the database.
			// 我们只需在try/catch中执行给定的回调。
			// 如果我们发现任何异常，我们可以回滚事务，以致没有任何更改被持久化到数据库中。
            try {
                $result = $callback($this);

                $this->getPdo()->exec('COMMIT TRAN');
            }

            // If we catch an exception, we will roll back so nothing gets messed
            // up in the database. Then we'll re-throw the exception so it can
            // be handled how the developer sees fit for their applications.
			// 如果我们发现异常，我们将回滚这样就不会有任何混乱在数据库中。
			// 然后我们将重新抛出异常，以便它可以按照开发人员认为适合其应用程序的方式进行处理。
            catch (Exception $e) {
                $this->getPdo()->exec('ROLLBACK TRAN');

                throw $e;
            } catch (Throwable $e) {
                $this->getPdo()->exec('ROLLBACK TRAN');

                throw $e;
            }

            return $result;
        }
    }

    /**
     * Get the default query grammar instance.
	 * 得到默认查询语法实例
     *
     * @return \Illuminate\Database\Query\Grammars\SqlServerGrammar
     */
    protected function getDefaultQueryGrammar()
    {
        return $this->withTablePrefix(new QueryGrammar);
    }

    /**
     * Get a schema builder instance for the connection.
	 * 得到连接的架构构建器实例
     *
     * @return \Illuminate\Database\Schema\SqlServerBuilder
     */
    public function getSchemaBuilder()
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new SqlServerBuilder($this);
    }

    /**
     * Get the default schema grammar instance.
	 * 得到默认模式语法实例
     *
     * @return \Illuminate\Database\Schema\Grammars\SqlServerGrammar
     */
    protected function getDefaultSchemaGrammar()
    {
        return $this->withTablePrefix(new SchemaGrammar);
    }

    /**
     * Get the default post processor instance.
	 * 得到默认请求进行实例
     *
     * @return \Illuminate\Database\Query\Processors\SqlServerProcessor
     */
    protected function getDefaultPostProcessor()
    {
        return new SqlServerProcessor;
    }

    /**
     * Get the Doctrine DBAL driver.
	 * 得到DBAL驱动
     *
     * @return \Doctrine\DBAL\Driver\PDOSqlsrv\Driver
     */
    protected function getDoctrineDriver()
    {
        if (! class_exists(DoctrineDriver::class)) {
            throw new LogicException(
                'Laravel v6 is only compatible with doctrine/dbal 2, in order to use this feature you must require the package "doctrine/dbal:^2.6".'
            );
			//Laravel v6仅与doctrin/dbal 2兼容，要使用此功能，您必须需要包"doctrin/dbal:^2.6".
        }

        return new DoctrineDriver;
    }
}
