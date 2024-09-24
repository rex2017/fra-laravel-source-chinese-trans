<?php
/**
 * 数据库用户提供者
 */

namespace Illuminate\Auth;

use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;

class DatabaseUserProvider implements UserProvider
{
    /**
     * The active database connection.
	 * 活动的数据库连接
     *
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $conn;

    /**
     * The hasher implementation.
	 * 哈希实现
     *
     * @var \Illuminate\Contracts\Hashing\Hasher
     */
    protected $hasher;

    /**
     * The table containing the users.
	 * 包含用户的表
     *
     * @var string
     */
    protected $table;

    /**
     * Create a new database user provider.
	 * 创建新的数据库用户提供者
     *
     * @param  \Illuminate\Database\ConnectionInterface  $conn
     * @param  \Illuminate\Contracts\Hashing\Hasher  $hasher
     * @param  string  $table
     * @return void
     */
    public function __construct(ConnectionInterface $conn, HasherContract $hasher, $table)
    {
        $this->conn = $conn;
        $this->table = $table;
        $this->hasher = $hasher;
    }

    /**
     * Retrieve a user by their unique identifier.
	 * 检索用户根据用户的唯一标识符
     *
     * @param  mixed  $identifier
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById($identifier)
    {
        $user = $this->conn->table($this->table)->find($identifier);

        return $this->getGenericUser($user);
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
	 * 检索用户根据用户的唯一标识符和"记住我"令牌
     *
     * @param  mixed  $identifier
     * @param  string  $token
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByToken($identifier, $token)
    {
        $user = $this->getGenericUser(
            $this->conn->table($this->table)->find($identifier)
        );

        return $user && $user->getRememberToken() && hash_equals($user->getRememberToken(), $token)
                    ? $user : null;
    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $token
     * @return void
     */
    public function updateRememberToken(UserContract $user, $token)
    {
        $this->conn->table($this->table)
                ->where($user->getAuthIdentifierName(), $user->getAuthIdentifier())
                ->update([$user->getRememberTokenName() => $token]);
    }

    /**
     * Retrieve a user by the given credentials.
	 * 检索用户根据给定的凭据
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        if (empty($credentials) ||
           (count($credentials) === 1 &&
            array_key_exists('password', $credentials))) {
            return;
        }

        // First we will add each credential element to the query as a where clause.
        // Then we can execute the query and, if we found a user, return it in a
        // generic "user" object that will be utilized by the Guard instances.
		// 首先，我们将把每个凭证元素作为where子句添加到查询中。
		// 然后我们可以执行查询，如果我们找到一个用户，则将其返回到一个通用的"user"对象中，
		// 该对象将由Guard实例使用。
        $query = $this->conn->table($this->table);

        foreach ($credentials as $key => $value) {
            if (Str::contains($key, 'password')) {
                continue;
            }

            if (is_array($value) || $value instanceof Arrayable) {
                $query->whereIn($key, $value);
            } else {
                $query->where($key, $value);
            }
        }

        // Now we are ready to execute the query to see if we have an user matching
        // the given credentials. If not, we will just return nulls and indicate
        // that there are no matching users for these given credential arrays.
		// 现在，我们已经准备好执行查询，查看是否有用户与给定的凭据匹配。
		// 如果没有，我们将只返回null，并指示这些给定的凭据数组没有匹配的用户。
        $user = $query->first();

        return $this->getGenericUser($user);
    }

    /**
     * Get the generic user.
	 * 得到通用用户
     *
     * @param  mixed  $user
     * @return \Illuminate\Auth\GenericUser|null
     */
    protected function getGenericUser($user)
    {
        if (! is_null($user)) {
            return new GenericUser((array) $user);
        }
    }

    /**
     * Validate a user against the given credentials.
	 * 根据给定的凭据验证用户
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $credentials
     * @return bool
     */
    public function validateCredentials(UserContract $user, array $credentials)
    {
        return $this->hasher->check(
            $credentials['password'], $user->getAuthPassword()
        );
    }
}
