<?php
/**
 * 数据库，SQListBuilder创建者
 */

namespace Illuminate\Database\Schema;

class SQLiteBuilder extends Builder
{
    /**
     * Drop all tables from the database.
	 * 删除所有表从数据库
     *
     * @return void
     */
    public function dropAllTables()
    {
        if ($this->connection->getDatabaseName() !== ':memory:') {
            return $this->refreshDatabaseFile();
        }

        $this->connection->select($this->grammar->compileEnableWriteableSchema());

        $this->connection->select($this->grammar->compileDropAllTables());

        $this->connection->select($this->grammar->compileDisableWriteableSchema());

        $this->connection->select($this->grammar->compileRebuild());
    }

    /**
     * Drop all views from the database.
	 * 删除所有视图从数据库
     *
     * @return void
     */
    public function dropAllViews()
    {
        $this->connection->select($this->grammar->compileEnableWriteableSchema());

        $this->connection->select($this->grammar->compileDropAllViews());

        $this->connection->select($this->grammar->compileDisableWriteableSchema());

        $this->connection->select($this->grammar->compileRebuild());
    }

    /**
     * Empty the database file.
	 * 清空数据库文件
     *
     * @return void
     */
    public function refreshDatabaseFile()
    {
        file_put_contents($this->connection->getDatabaseName(), '');
    }
}
