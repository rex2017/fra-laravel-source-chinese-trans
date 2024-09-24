<?php
/**
 * 必须验证电子邮件
 */

namespace Illuminate\Auth;

use Illuminate\Auth\Notifications\VerifyEmail;

trait MustVerifyEmail
{
    /**
     * Determine if the user has verified their email address.
	 * 确定用户是否验证了他们的电子邮件地址
     *
     * @return bool
     */
    public function hasVerifiedEmail()
    {
        return ! is_null($this->email_verified_at);
    }

    /**
     * Mark the given user's email as verified.
	 * 标记给定用户的电子邮件为已验证
     *
     * @return bool
     */
    public function markEmailAsVerified()
    {
        return $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    /**
     * Send the email verification notification.
	 * 发送邮件验证通知
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmail);
    }

    /**
     * Get the email address that should be used for verification.
	 * 得到应该用于验证的电子邮件地址
     *
     * @return string
     */
    public function getEmailForVerification()
    {
        return $this->email;
    }
}
