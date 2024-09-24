<?php
/**
 * 契约，强制验证邮件接口
 */

namespace Illuminate\Contracts\Auth;

interface MustVerifyEmail
{
    /**
     * Determine if the user has verified their email address.
	 * 确定用户是否验证了他们的邮件地址
     *
     * @return bool
     */
    public function hasVerifiedEmail();

    /**
     * Mark the given user's email as verified.
	 * 标记给定的用户邮箱为已验证
     *
     * @return bool
     */
    public function markEmailAsVerified();

    /**
     * Send the email verification notification.
	 * 发送邮件验证通知
     *
     * @return void
     */
    public function sendEmailVerificationNotification();

    /**
     * Get the email address that should be used for verification.
	 * 得到应该用于验证的邮件地址
     *
     * @return string
     */
    public function getEmailForVerification();
}
