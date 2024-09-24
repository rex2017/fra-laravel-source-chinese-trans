<?php
/**
 * 队列，空连接
 */

namespace Illuminate\Queue\Connectors;

use Illuminate\Queue\NullQueue;

class NullConnector implements ConnectorInterface
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
        return new NullQueue;
    }
}
