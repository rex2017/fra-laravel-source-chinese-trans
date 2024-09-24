<?php
/**
 * 通知，数据库通知
 */

namespace Illuminate\Notifications;

use Illuminate\Database\Eloquent\Model;

class DatabaseNotification extends Model
{
    /**
     * The "type" of the primary key ID.
	 * 主键ID的"类型"
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
	 * 指明id是否自动递增
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The table associated with the model.
	 * 与模型相关联的表
     *
     * @var string
     */
    protected $table = 'notifications';

    /**
     * The guarded attributes on the model.
	 * 模型上受保护的属性
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
	 * 应该转换为本机类型的属性
     *
     * @var array
     */
    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    /**
     * Get the notifiable entity that the notification belongs to.
	 * 得到通知所属的可通知实体
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function notifiable()
    {
        return $this->morphTo();
    }

    /**
     * Mark the notification as read.
	 * 标记通知为已读
     *
     * @return void
     */
    public function markAsRead()
    {
        if (is_null($this->read_at)) {
            $this->forceFill(['read_at' => $this->freshTimestamp()])->save();
        }
    }

    /**
     * Mark the notification as unread.
	 * 标记通知为未读
     *
     * @return void
     */
    public function markAsUnread()
    {
        if (! is_null($this->read_at)) {
            $this->forceFill(['read_at' => null])->save();
        }
    }

    /**
     * Determine if a notification has been read.
	 * 确定是否通知已读取
     *
     * @return bool
     */
    public function read()
    {
        return $this->read_at !== null;
    }

    /**
     * Determine if a notification has not been read.
	 * 确定是否通知未读取
     *
     * @return bool
     */
    public function unread()
    {
        return $this->read_at === null;
    }

    /**
     * Create a new database notification collection instance.
	 * 创建新的数据库通知集合实例
     *
     * @param  array  $models
     * @return \Illuminate\Notifications\DatabaseNotificationCollection
     */
    public function newCollection(array $models = [])
    {
        return new DatabaseNotificationCollection($models);
    }
}
