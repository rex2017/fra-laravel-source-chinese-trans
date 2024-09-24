<?php
/**
 * Session存在感知界面
 */

namespace Illuminate\Session;

interface ExistenceAwareInterface
{
    /**
     * Set the existence state for the session.
	 * 设置会话的存在状态
     *
     * @param  bool  $value
     * @return \SessionHandlerInterface
     */
    public function setExists($value);
}
