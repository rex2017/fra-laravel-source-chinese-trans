<?php
/**
 * 数据库MySql连接
 */

namespace Illuminate\Database;

use Doctrine\DBAL\Driver\PDOMySql\Driver as DoctrineDriver;
use Illuminate\Database\Query\Grammars\MySqlGrammar as QueryGrammar;
use Illuminate\Database\Query\Processors\MySqlProcessor;
use Illuminate\Database\Schema\Grammars\MySqlGrammar as SchemaGrammar;
use Illuminate\Database\Schema\MySqlBuilder;
use LogicException;

class MySqlConnection extends Connection
{
    /**
     * Get the default query grammar instance.
	 * 得到默认查询语法实例
     *
     * @return \Illuminate\Database\Query\Grammars\MySqlGrammar
     */
    protected function getDefaultQueryGrammar()
    {
        return $this->withTablePrefix(new QueryGrammar);
    }

    /**
     * Get a schema builder instance for the connection.
	 * 得到连接的架构构建器实例
     *
     * @return \Illuminate\Database\Schema\MySqlBuilder
     */
    public function getSchemaBuilder()
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new MySqlBuilder($this);
    }

    /**
     * Get the default schema grammar instance.
	 * 得到默认查询语法实例
     *
     * @return \Illuminate\Database\Schema\Grammars\MySqlGrammar
     */
    protected function getDefaultSchemaGrammar()
    {
        return $this->withTablePrefix(new SchemaGrammar);
    }

    /**
     * Get the default post processor instance.
	 * 得到默认处理实例
     *
     * @return \Illuminate\Database\Query\Processors\MySqlProcessor
     */
    protected function getDefaultPostProcessor()
    {
        return new MySqlProcessor;
    }

    /**
     * Get the Doctrine DBAL driver.
	 * 得到Doctrine DBAL驱动程序
     *
     * @return \Doctrine\DBAL\Driver\PDOMySql\Driver
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
