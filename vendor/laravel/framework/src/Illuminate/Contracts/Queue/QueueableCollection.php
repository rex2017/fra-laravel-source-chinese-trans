<?php
/**
 * 契约，可排队集合接口
 */

namespace Illuminate\Contracts\Queue;

interface QueueableCollection
{
    /**
     * Get the type of the entities being queued.
	 * 得到排队实体类型
     *
     * @return string|null
     */
    public function getQueueableClass();

    /**
     * Get the identifiers for all of the entities.
	 * 得到队列的实体ID
     *
     * @return array
     */
    public function getQueueableIds();

    /**
     * Get the relationships of the entities being queued.
	 * 得到正在队列的实本关系
     *
     * @return array
     */
    public function getQueueableRelations();

    /**
     * Get the connection of the entities being queued.
	 * 得到正在队列的实体连接
     *
     * @return string|null
     */
    public function getQueueableConnection();
}
