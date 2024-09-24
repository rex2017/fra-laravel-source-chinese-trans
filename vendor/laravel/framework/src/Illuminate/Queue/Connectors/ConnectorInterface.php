<?php
/**
 * 队列，连接接口
 */

namespace Illuminate\Queue\Connectors;

interface ConnectorInterface
{
    /**
     * Establish a queue connection.
	 * 建立队列连接
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connect(array $config);
}
