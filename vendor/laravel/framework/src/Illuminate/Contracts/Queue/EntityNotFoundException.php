<?php
/**
 * 契约，队列实体未找到异常接口
 */

namespace Illuminate\Contracts\Queue;

use InvalidArgumentException;

class EntityNotFoundException extends InvalidArgumentException
{
    /**
     * Create a new exception instance.
	 * 创建新异常实例
     *
     * @param  string  $type
     * @param  mixed  $id
     * @return void
     */
    public function __construct($type, $id)
    {
        $id = (string) $id;

        parent::__construct("Queueable entity [{$type}] not found for ID [{$id}].");
    }
}
