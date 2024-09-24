<?php
/**
 * 基础，在数据库中
 */

namespace Illuminate\Foundation\Testing\Constraints;

use Illuminate\Database\Connection;
use PHPUnit\Framework\Constraint\Constraint;

class HasInDatabase extends Constraint
{
    /**
     * Number of records that will be shown in the console in case of failure.
	 * 在发生故障时将在控制台中显示的记录数
     *
     * @var int
     */
    protected $show = 3;

    /**
     * The database connection.
	 * 数据库连接
     *
     * @var \Illuminate\Database\Connection
     */
    protected $database;

    /**
     * The data that will be used to narrow the search in the database table.
	 * 将用于缩小数据库表中搜索范围的数据
     *
     * @var array
     */
    protected $data;

    /**
     * Create a new constraint instance.
	 * 创建新的约束实例
     *
     * @param  \Illuminate\Database\Connection  $database
     * @param  array  $data
     * @return void
     */
    public function __construct(Connection $database, array $data)
    {
        $this->data = $data;

        $this->database = $database;
    }

    /**
     * Check if the data is found in the given table.
	 * 检查是否在给定的表中找到数据
     *
     * @param  string  $table
     * @return bool
     */
    public function matches($table): bool
    {
        return $this->database->table($table)->where($this->data)->count() > 0;
    }

    /**
     * Get the description of the failure.
	 * 得到失败描述
     *
     * @param  string  $table
     * @return string
     */
    public function failureDescription($table): string
    {
        return sprintf(
            "a row in the table [%s] matches the attributes %s.\n\n%s",
            $table, $this->toString(JSON_PRETTY_PRINT), $this->getAdditionalInfo($table)
        );
    }

    /**
     * Get additional info about the records found in the database table.
	 * 得到关于在数据库表中找到的记录的其他信息
     *
     * @param  string  $table
     * @return string
     */
    protected function getAdditionalInfo($table)
    {
        $query = $this->database->table($table);

        $similarResults = $query->where(
            array_key_first($this->data),
            $this->data[array_key_first($this->data)]
        )->limit($this->show)->get();

        if ($similarResults->isNotEmpty()) {
            $description = 'Found similar results: '.json_encode($similarResults, JSON_PRETTY_PRINT);
        } else {
            $query = $this->database->table($table);

            $results = $query->limit($this->show)->get();

            if ($results->isEmpty()) {
                return 'The table is empty.';
            }

            $description = 'Found: '.json_encode($results, JSON_PRETTY_PRINT);
        }

        if ($query->count() > $this->show) {
            $description .= sprintf(' and %s others', $query->count() - $this->show);
        }

        return $description;
    }

    /**
     * Get a string representation of the object.
	 * 得到对象的字符串表示形式
     *
     * @param  int  $options
     * @return string
     */
    public function toString($options = 0): string
    {
        return json_encode($this->data, $options);
    }
}
