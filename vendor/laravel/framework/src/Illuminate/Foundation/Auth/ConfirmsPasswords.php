<?php
/**
 * 基础，确认密码
 */

namespace Illuminate\Foundation\Auth;

use Illuminate\Http\Request;

trait ConfirmsPasswords
{
    use RedirectsUsers;

    /**
     * Display the password confirmation view.
	 * 显示密码确认视图
     *
     * @return \Illuminate\Http\Response
     */
    public function showConfirmForm()
    {
        return view('auth.passwords.confirm');
    }

    /**
     * Confirm the given user's password.
	 * 确认给定用户的密码
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function confirm(Request $request)
    {
        $request->validate($this->rules(), $this->validationErrorMessages());

        $this->resetPasswordConfirmationTimeout($request);

        return redirect()->intended($this->redirectPath());
    }

    /**
     * Reset the password confirmation timeout.
	 * 重置密码确认超时时间
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function resetPasswordConfirmationTimeout(Request $request)
    {
        $request->session()->put('auth.password_confirmed_at', time());
    }

    /**
     * Get the password confirmation validation rules.
	 * 得到密码确认验证规则
     *
     * @return array
     */
    protected function rules()
    {
        return [
            'password' => 'required|password',
        ];
    }

    /**
     * Get the password confirmation validation error messages.
	 * 得到密码确认验证错误消息
     *
     * @return array
     */
    protected function validationErrorMessages()
    {
        return [];
    }
}
