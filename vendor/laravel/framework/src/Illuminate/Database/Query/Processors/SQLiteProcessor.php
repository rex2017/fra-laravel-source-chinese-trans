<?php
/**
 * 数据库，SQLite进程
 */

namespace Illuminate\Database\Query\Processors;

class SQLiteProcessor extends Processor
{
    /**
     * Process the results of a column listing query.
	 * 处理列清单查询的结果
     *
     * @param  array  $results
     * @return array
     */
    public function processColumnListing($results)
    {
        return array_map(function ($result) {
            return ((object) $result)->name;
        }, $results);
    }
}
