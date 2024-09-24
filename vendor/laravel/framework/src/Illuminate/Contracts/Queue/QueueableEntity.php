<?php
/**
 * 契约，可排队实体接口
 */

namespace Illuminate\Contracts\Queue;

interface QueueableEntity
{
    /**
     * Get the queueable identity for the entity.
	 * 得到实体的可排队标识
     *
     * @return mixed
     */
    public function getQueueableId();

    /**
     * Get the relationships for the entity.
	 * 得到实体关系
     *
     * @return array
     */
    public function getQueueableRelations();

    /**
     * Get the connection of the entity.
	 * 得到实体连接
     *
     * @return string|null
     */
    public function getQueueableConnection();
}
