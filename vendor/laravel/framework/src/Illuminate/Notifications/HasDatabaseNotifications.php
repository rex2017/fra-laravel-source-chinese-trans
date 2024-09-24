<?php
/**
 * 通知，有数据库通知
 */

namespace Illuminate\Notifications;

trait HasDatabaseNotifications
{
    /**
     * Get the entity's notifications.
	 * 得到实体的通知
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function notifications()
    {
        return $this->morphMany(DatabaseNotification::class, 'notifiable')->orderBy('created_at', 'desc');
    }

    /**
     * Get the entity's read notifications.
	 * 得到实体的已读通知
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function readNotifications()
    {
        return $this->notifications()->whereNotNull('read_at');
    }

    /**
     * Get the entity's unread notifications.
	 * 得到实体的未读通知
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function unreadNotifications()
    {
        return $this->notifications()->whereNull('read_at');
    }
}
