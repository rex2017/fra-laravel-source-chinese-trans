<?php
/**
 * 路由，信道
 */

/*
|--------------------------------------------------------------------------
| Broadcast Channels 	广播信道
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
| 这里你可以注册所有你的应用提供的事件广播信道。
| 给定的信道授权回调是用于检查经过身份验证的用户是否可以侦听信道。
|
*/

Broadcast::channel('App.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
