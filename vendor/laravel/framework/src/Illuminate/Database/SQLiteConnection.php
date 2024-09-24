<?php
/**
 * 数据库SQLite连接
 */

namespace Illuminate\Database;

use Doctrine\DBAL\Driver\PDOSqlite\Driver as DoctrineDriver;
use Illuminate\Database\Query\Grammars\SQLiteGrammar as QueryGrammar;
use Illuminate\Database\Query\Processors\SQLiteProcessor;
use Illuminate\Database\Schema\Grammars\SQLiteGrammar as SchemaGrammar;
use Illuminate\Database\Schema\SQLiteBuilder;
use LogicException;

class SQLiteConnection extends Connection
{
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
        parent::__construct($pdo, $database, $tablePrefix, $config);

        $enableForeignKeyConstraints = $this->getForeignKeyConstraintsConfigurationValue();

        if ($enableForeignKeyConstraints === null) {
            return;
        }

        $enableForeignKeyConstraints
            ? $this->getSchemaBuilder()->enableForeignKeyConstraints()
            : $this->getSchemaBuilder()->disableForeignKeyConstraints();
    }

    /**
     * Get the default query grammar instance.
	 * 得到默认查询语法实例
     *
     * @return \Illuminate\Database\Query\Grammars\SQLiteGrammar
     */
    protected function getDefaultQueryGrammar()
    {
        return $this->withTablePrefix(new QueryGrammar);
    }

    /**
     * Get a schema builder instance for the connection.
	 * 得到连接的架构构建器实例
     *
     * @return \Illuminate\Database\Schema\SQLiteBuilder
     */
    public function getSchemaBuilder()
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new SQLiteBuilder($this);
    }

    /**
     * Get the default schema grammar instance.
	 * 得到默认语法实例
     *
     * @return \Illuminate\Database\Schema\Grammars\SQLiteGrammar
     */
    protected function getDefaultSchemaGrammar()
    {
        return $this->withTablePrefix(new SchemaGrammar);
    }

    /**
     * Get the default post processor instance.
	 * 得到默认的post处理实例
     *
     * @return \Illuminate\Database\Query\Processors\SQLiteProcessor
     */
    protected function getDefaultPostProcessor()
    {
        return new SQLiteProcessor;
    }

    /**
     * Get the Doctrine DBAL driver.
	 * 得到Doctrine DBAL驱动程序
     *
     * @return \Doctrine\DBAL\Driver\PDOSqlite\Driver
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

    /**
     * Get the database connection foreign key constraints configuration option.
	 * 得到数据库连接外键约束配置选项
     *
     * @return bool|null
     */
    protected function getForeignKeyConstraintsConfigurationValue()
    {
        return $this->getConfig('foreign_key_constraints');
    }
}
