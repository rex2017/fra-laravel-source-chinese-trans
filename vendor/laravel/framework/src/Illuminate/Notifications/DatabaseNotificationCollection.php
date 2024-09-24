<?php
/**
 * 通知，数据库通知
 */

namespace Illuminate\Notifications;

use Illuminate\Database\Eloquent\Collection;

class DatabaseNotificationCollection extends Collection
{
    /**
     * Mark all notifications as read.
	 * 标记所有通知为已读
     *
     * @return void
     */
    public function markAsRead()
    {
        $this->each->markAsRead();
    }

    /**
     * Mark all notifications as unread.
	 * 标记所有通知为未读
     *
     * @return void
     */
    public function markAsUnread()
    {
        $this->each->markAsUnread();
    }
}
