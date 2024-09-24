<?php
/**
 * 令牌守卫
 */

namespace Illuminate\Auth;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;

class TokenGuard implements Guard
{
    use GuardHelpers;

    /**
     * The request instance.
	 * 请求实例
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * The name of the query string item from the request containing the API token.
	 * 来自包含API令牌的请求的查询字符串项的名称
     *
     * @var string
     */
    protected $inputKey;

    /**
     * The name of the token "column" in persistent storage.
	 * 持久存储中令牌"column"的名称
     *
     * @var string
     */
    protected $storageKey;

    /**
     * Indicates if the API token is hashed in storage.
	 * 指明API令牌是否在存储中散列
     *
     * @var bool
     */
    protected $hash = false;

    /**
     * Create a new authentication guard.
	 * 创建新的身份验证保护
     *
     * @param  \Illuminate\Contracts\Auth\UserProvider  $provider
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $inputKey
     * @param  string  $storageKey
     * @param  bool  $hash
     * @return void
     */
    public function __construct(
        UserProvider $provider,
        Request $request,
        $inputKey = 'api_token',
        $storageKey = 'api_token',
        $hash = false)
    {
        $this->hash = $hash;
        $this->request = $request;
        $this->provider = $provider;
        $this->inputKey = $inputKey;
        $this->storageKey = $storageKey;
    }

    /**
     * Get the currently authenticated user.
	 * 得到当前已验证用户
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {
        // If we've already retrieved the user for the current request we can just
        // return it back immediately. We do not want to fetch the user data on
        // every call to this method because that would be tremendously slow.
		// 如果我们已经检索到当前请求的用户，我们可以立即将其返回。
		// 我们不想在每次调用此方法时都获取用户数据，因为这会非常慢。
        if (! is_null($this->user)) {
            return $this->user;
        }

        $user = null;

        $token = $this->getTokenForRequest();

        if (! empty($token)) {
            $user = $this->provider->retrieveByCredentials([
                $this->storageKey => $this->hash ? hash('sha256', $token) : $token,
            ]);
        }

        return $this->user = $user;
    }

    /**
     * Get the token for the current request.
	 * 得令牌当前请求
     *
     * @return string
     */
    public function getTokenForRequest()
    {
        $token = $this->request->query($this->inputKey);

        if (empty($token)) {
            $token = $this->request->input($this->inputKey);
        }

        if (empty($token)) {
            $token = $this->request->bearerToken();
        }

        if (empty($token)) {
            $token = $this->request->getPassword();
        }

        return $token;
    }

    /**
     * Validate a user's credentials.
	 * 验证用户的凭据
     *
     * @param  array  $credentials
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        if (empty($credentials[$this->inputKey])) {
            return false;
        }

        $credentials = [$this->storageKey => $credentials[$this->inputKey]];

        if ($this->provider->retrieveByCredentials($credentials)) {
            return true;
        }

        return false;
    }

    /**
     * Set the current request instance.
	 * 设置当前请求实例
     *
     * @param  \Illuminate\Http\Request  $request
     * @return $this
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }
}
