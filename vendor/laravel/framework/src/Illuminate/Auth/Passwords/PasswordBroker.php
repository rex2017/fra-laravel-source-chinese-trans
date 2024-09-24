<?php
/**
 * 身份，密码代理
 */

namespace Illuminate\Auth\Passwords;

use Closure;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Contracts\Auth\PasswordBroker as PasswordBrokerContract;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Arr;
use UnexpectedValueException;

class PasswordBroker implements PasswordBrokerContract
{
    /**
     * The password token repository.
	 * 密码令牌存储库
     *
     * @var \Illuminate\Auth\Passwords\TokenRepositoryInterface
     */
    protected $tokens;

    /**
     * The user provider implementation.
	 * 用户提供者实现
     *
     * @var \Illuminate\Contracts\Auth\UserProvider
     */
    protected $users;

    /**
     * Create a new password broker instance.
	 * 创建新的密码代理实例
     *
     * @param  \Illuminate\Auth\Passwords\TokenRepositoryInterface  $tokens
     * @param  \Illuminate\Contracts\Auth\UserProvider  $users
     * @return void
     */
    public function __construct(TokenRepositoryInterface $tokens, UserProvider $users)
    {
        $this->users = $users;
        $this->tokens = $tokens;
    }

    /**
     * Send a password reset link to a user.
	 * 向用户发送密码重置链接
     *
     * @param  array  $credentials
     * @return string
     */
    public function sendResetLink(array $credentials)
    {
        // First we will check to see if we found a user at the given credentials and
        // if we did not we will redirect back to this current URI with a piece of
        // "flash" data in the session to indicate to the developers the errors.
		// 首先，我们将检查是否在给定的凭据中找到了用户，如果没有，
		// 我们将在会话中使用一段"flash"数据重定向回当前URI，以向开发人员指示错误。
        $user = $this->getUser($credentials);

        if (is_null($user)) {
            return static::INVALID_USER;
        }

        if (method_exists($this->tokens, 'recentlyCreatedToken') &&
            $this->tokens->recentlyCreatedToken($user)) {
            return static::RESET_THROTTLED;
        }

        // Once we have the reset token, we are ready to send the message out to this
        // user with a link to reset their password. We will then redirect back to
        // the current URI having nothing set in the session to indicate errors.
		// 一旦我们有了重置令牌，我们就可以向该用户发送消息，并附上重置密码的链接。
		// 然后，我们将重定向回会话中没有设置任何指示错误的当前URI。
        $user->sendPasswordResetNotification(
            $this->tokens->create($user)
        );

        return static::RESET_LINK_SENT;
    }

    /**
     * Reset the password for the given token.
	 * 重置给定令牌的密码
     *
     * @param  array  $credentials
     * @param  \Closure  $callback
     * @return mixed
     */
    public function reset(array $credentials, Closure $callback)
    {
        $user = $this->validateReset($credentials);

        // If the responses from the validate method is not a user instance, we will
        // assume that it is a redirect and simply return it from this method and
        // the user is properly redirected having an error message on the post.
		// 如果来自validate方法的响应不是用户实例，我们将假设它是一个重定向，
		// 并简单地从该方法返回它，并且用户被正确重定向，并在帖子上显示错误消息。
        if (! $user instanceof CanResetPasswordContract) {
            return $user;
        }

        $password = $credentials['password'];

        // Once the reset has been validated, we'll call the given callback with the
        // new password. This gives the user an opportunity to store the password
        // in their persistent storage. Then we'll delete the token and return.
		// 一旦重置被验证，我们将使用新密码调用给定的回调。
		// 这为用户提供了将密码存储在持久存储器中的机会。然后我们将删除令牌并返回。
        $callback($user, $password);

        $this->tokens->delete($user);

        return static::PASSWORD_RESET;
    }

    /**
     * Validate a password reset for the given credentials.
	 * 验证给定凭据的密码重置
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\CanResetPassword|string
     */
    protected function validateReset(array $credentials)
    {
        if (is_null($user = $this->getUser($credentials))) {
            return static::INVALID_USER;
        }

        if (! $this->tokens->exists($user, $credentials['token'])) {
            return static::INVALID_TOKEN;
        }

        return $user;
    }

    /**
     * Get the user for the given credentials.
	 * 得到给定凭据的用户
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\CanResetPassword|null
     *
     * @throws \UnexpectedValueException
     */
    public function getUser(array $credentials)
    {
        $credentials = Arr::except($credentials, ['token']);

        $user = $this->users->retrieveByCredentials($credentials);

        if ($user && ! $user instanceof CanResetPasswordContract) {
            throw new UnexpectedValueException('User must implement CanResetPassword interface.');
        }

        return $user;
    }

    /**
     * Create a new password reset token for the given user.
	 * 创建一个新的密码重置令牌为给定用户
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
     * @return string
     */
    public function createToken(CanResetPasswordContract $user)
    {
        return $this->tokens->create($user);
    }

    /**
     * Delete password reset tokens of the given user.
	 * 删除给定用户的密码重置令牌
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
     * @return void
     */
    public function deleteToken(CanResetPasswordContract $user)
    {
        $this->tokens->delete($user);
    }

    /**
     * Validate the given password reset token.
	 * 验证给定的密码重置令牌
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
     * @param  string  $token
     * @return bool
     */
    public function tokenExists(CanResetPasswordContract $user, $token)
    {
        return $this->tokens->exists($user, $token);
    }

    /**
     * Get the password reset token repository implementation.
	 * 得到密码重置令牌存储库实现
     *
     * @return \Illuminate\Auth\Passwords\TokenRepositoryInterface
     */
    public function getRepository()
    {
        return $this->tokens;
    }
}
