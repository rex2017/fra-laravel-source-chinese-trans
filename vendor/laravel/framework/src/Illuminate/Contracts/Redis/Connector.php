<?php
/**
 * 契约，连接器接口
 */

namespace Illuminate\Contracts\Redis;

interface Connector
{
    /**
     * Create a connection to a Redis cluster.
	 * 创建新的Redis群连接
     *
     * @param  array  $config
     * @param  array  $options
     * @return \Illuminate\Redis\Connections\Connection
     */
    public function connect(array $config, array $options);

    /**
     * Create a connection to a Redis instance.
	 * 创建新的Redis连接实例
     *
     * @param  array  $config
     * @param  array  $clusterOptions
     * @param  array  $options
     * @return \Illuminate\Redis\Connections\Connection
     */
    public function connectToCluster(array $config, array $clusterOptions, array $options);
}
