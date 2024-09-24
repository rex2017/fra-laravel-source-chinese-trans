<?php
/**
 * 数据库，SQLite连接器
 */

namespace Illuminate\Database\Connectors;

use InvalidArgumentException;

class SQLiteConnector extends Connector implements ConnectorInterface
{
    /**
     * Establish a database connection.
	 * 建立数据库连接
     *
     * @param  array  $config
     * @return \PDO
     *
     * @throws \InvalidArgumentException
     */
    public function connect(array $config)
    {
        $options = $this->getOptions($config);

        // SQLite supports "in-memory" databases that only last as long as the owning
        // connection does. These are useful for tests or for short lifetime store
        // querying. In-memory databases may only have a single open connection.
		// SQLite支持"内存中"数据库，其持续时间与所属连接的持续时间相同。
		// 这些对于测试或短期存储查询非常有用。
		// 内存数据库可能只有一个打开的连接。
        if ($config['database'] === ':memory:') {
            return $this->createConnection('sqlite::memory:', $config, $options);
        }

        $path = realpath($config['database']);

        // Here we'll verify that the SQLite database exists before going any further
        // as the developer probably wants to know if the database exists and this
        // SQLite driver will not throw any exception if it does not by default.
		// 在这里我们将在进一步操作之前验证SQLite数据库是否存在，
		// 因为开发人员可能想知道数据库是否存在，
		// 如果默认情况下不存在，这个SQLite驱动程序不会抛出任何异常。
        if ($path === false) {
            throw new InvalidArgumentException("Database ({$config['database']}) does not exist.");
        }

        return $this->createConnection("sqlite:{$path}", $config, $options);
    }
}
