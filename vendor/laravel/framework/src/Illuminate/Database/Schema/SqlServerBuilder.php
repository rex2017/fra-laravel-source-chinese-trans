<?php
/**
 * 数据库，SqlServer创建者
 */

namespace Illuminate\Database\Schema;

class SqlServerBuilder extends Builder
{
    /**
     * Drop all tables from the database.
	 * 删除所有表从数据库
     *
     * @return void
     */
    public function dropAllTables()
    {
        $this->connection->statement($this->grammar->compileDropAllForeignKeys());

        $this->connection->statement($this->grammar->compileDropAllTables());
    }

    /**
     * Drop all views from the database.
	 * 删除所有视图从数据库
     *
     * @return void
     */
    public function dropAllViews()
    {
        $this->connection->statement($this->grammar->compileDropAllViews());
    }
}
