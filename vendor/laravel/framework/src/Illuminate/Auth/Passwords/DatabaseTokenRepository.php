<?php
/**
 * 身份，数据库令牌存储库
 */

namespace Illuminate\Auth\Passwords;

use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DatabaseTokenRepository implements TokenRepositoryInterface
{
    /**
     * The database connection instance.
	 * 数据库连接实例
     *
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * The Hasher implementation.
	 * 哈希实现
     *
     * @var \Illuminate\Contracts\Hashing\Hasher
     */
    protected $hasher;

    /**
     * The token database table.
	 * 令牌数据库表
     *
     * @var string
     */
    protected $table;

    /**
     * The hashing key.
	 * 哈希键
     *
     * @var string
     */
    protected $hashKey;

    /**
     * The number of seconds a token should last.
	 * 令牌应该持续的秒数
     *
     * @var int
     */
    protected $expires;

    /**
     * Minimum number of seconds before re-redefining the token.
	 * 重新定义令牌之前的最小秒数
     *
     * @var int
     */
    protected $throttle;

    /**
     * Create a new token repository instance.
	 * 创建新的令牌存储库实例
     *
     * @param  \Illuminate\Database\ConnectionInterface  $connection
     * @param  \Illuminate\Contracts\Hashing\Hasher  $hasher
     * @param  string  $table
     * @param  string  $hashKey
     * @param  int  $expires
     * @param  int  $throttle
     * @return void
     */
    public function __construct(ConnectionInterface $connection, HasherContract $hasher,
                                $table, $hashKey, $expires = 60,
                                $throttle = 60)
    {
        $this->table = $table;
        $this->hasher = $hasher;
        $this->hashKey = $hashKey;
        $this->expires = $expires * 60;
        $this->connection = $connection;
        $this->throttle = $throttle;
    }

    /**
     * Create a new token record.
	 * 创建新的令牌记录
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
     * @return string
     */
    public function create(CanResetPasswordContract $user)
    {
        $email = $user->getEmailForPasswordReset();

        $this->deleteExisting($user);

        // We will create a new, random token for the user so that we can e-mail them
        // a safe link to the password reset form. Then we will insert a record in
        // the database so that we can verify the token within the actual reset.
		// 我们将为用户创建一个新的随机令牌，以便我们可以通过电子邮件向他们发送密码重置表单的安全链接。
		// 然后，我们将在数据库中插入一条记录，以便我们在实际重置中验证令牌。
        $token = $this->createNewToken();

        $this->getTable()->insert($this->getPayload($email, $token));

        return $token;
    }

    /**
     * Delete all existing reset tokens from the database.
	 * 删除所有现有的重置令牌从数据库中
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
     * @return int
     */
    protected function deleteExisting(CanResetPasswordContract $user)
    {
        return $this->getTable()->where('email', $user->getEmailForPasswordReset())->delete();
    }

    /**
     * Build the record payload for the table.
	 * 为表构建记录有效负载
     *
     * @param  string  $email
     * @param  string  $token
     * @return array
     */
    protected function getPayload($email, $token)
    {
        return ['email' => $email, 'token' => $this->hasher->make($token), 'created_at' => new Carbon];
    }

    /**
     * Determine if a token record exists and is valid.
	 * 确定令牌记录是否存在并且有效
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
     * @param  string  $token
     * @return bool
     */
    public function exists(CanResetPasswordContract $user, $token)
    {
        $record = (array) $this->getTable()->where(
            'email', $user->getEmailForPasswordReset()
        )->first();

        return $record &&
               ! $this->tokenExpired($record['created_at']) &&
                 $this->hasher->check($token, $record['token']);
    }

    /**
     * Determine if the token has expired.
	 * 确定令牌是否已过期
     *
     * @param  string  $createdAt
     * @return bool
     */
    protected function tokenExpired($createdAt)
    {
        return Carbon::parse($createdAt)->addSeconds($this->expires)->isPast();
    }

    /**
     * Determine if the given user recently created a password reset token.
	 * 确定给定用户最近是否创建了密码重置令牌
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
     * @return bool
     */
    public function recentlyCreatedToken(CanResetPasswordContract $user)
    {
        $record = (array) $this->getTable()->where(
            'email', $user->getEmailForPasswordReset()
        )->first();

        return $record && $this->tokenRecentlyCreated($record['created_at']);
    }

    /**
     * Determine if the token was recently created.
	 * 确定是否最近创建了令牌
     *
     * @param  string  $createdAt
     * @return bool
     */
    protected function tokenRecentlyCreated($createdAt)
    {
        if ($this->throttle <= 0) {
            return false;
        }

        return Carbon::parse($createdAt)->addSeconds(
            $this->throttle
        )->isFuture();
    }

    /**
     * Delete a token record by user.
	 * 删除令牌记录按用户
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
     * @return void
     */
    public function delete(CanResetPasswordContract $user)
    {
        $this->deleteExisting($user);
    }

    /**
     * Delete expired tokens.
	 * 删除超时令牌
     *
     * @return void
     */
    public function deleteExpired()
    {
        $expiredAt = Carbon::now()->subSeconds($this->expires);

        $this->getTable()->where('created_at', '<', $expiredAt)->delete();
    }

    /**
     * Create a new token for the user.
	 * 创建一个新令牌为用户
     *
     * @return string
     */
    public function createNewToken()
    {
        return hash_hmac('sha256', Str::random(40), $this->hashKey);
    }

    /**
     * Get the database connection instance.
	 * 得到数据库连接实例
     *
     * @return \Illuminate\Database\ConnectionInterface
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Begin a new database query against the table.
	 * 开始一个新的数据库查询对表
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function getTable()
    {
        return $this->connection->table($this->table);
    }

    /**
     * Get the hasher instance.
	 * 得到哈希实例
     *
     * @return \Illuminate\Contracts\Hashing\Hasher
     */
    public function getHasher()
    {
        return $this->hasher;
    }
}
