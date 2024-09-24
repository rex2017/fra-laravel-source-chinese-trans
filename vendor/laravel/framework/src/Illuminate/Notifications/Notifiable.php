<?php
/**
 * 应通知的，待完善特征
 */

namespace Illuminate\Notifications;

trait Notifiable
{
    use HasDatabaseNotifications, RoutesNotifications;
}
