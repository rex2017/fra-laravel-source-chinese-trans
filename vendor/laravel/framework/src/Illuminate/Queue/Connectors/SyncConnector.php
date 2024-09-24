<?php
/**
 * 队列，Sync连接器
 */

namespace Illuminate\Queue\Connectors;

use Illuminate\Queue\SyncQueue;

class SyncConnector implements ConnectorInterface
{
    /**
     * Establish a queue connection.
	 * 建立队列连接
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        return new SyncQueue;
    }
}
