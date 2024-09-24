<?php
/**
 * 契约，SESSION接口
 */

namespace Illuminate\Contracts\Session;

interface Session
{
    /**
     * Get the name of the session.
	 * 得到SESSION的名称
     *
     * @return string
     */
    public function getName();

    /**
     * Get the current session ID.
	 * 得到SESSION的ID
     *
     * @return string
     */
    public function getId();

    /**
     * Set the session ID.
	 * 设置SESSION的ID
     *
     * @param  string  $id
     * @return void
     */
    public function setId($id);

    /**
     * Start the session, reading the data from a handler.
	 * 开始一个SESSION，从处理程序读取数据。
     *
     * @return bool
     */
    public function start();

    /**
     * Save the session data to storage.
	 * 保存SESSION至存储
     *
     * @return void
     */
    public function save();

    /**
     * Get all of the session data.
	 * 得到所有的SESSION数据
     *
     * @return array
     */
    public function all();

    /**
     * Checks if a key exists.
	 * 检查KEY是否存在
     *
     * @param  string|array  $key
     * @return bool
     */
    public function exists($key);

    /**
     * Checks if a key is present and not null.
	 * 检查一个KEY是否存在且不为空
     *
     * @param  string|array  $key
     * @return bool
     */
    public function has($key);

    /**
     * Get an item from the session.
	 * 得到一个项目从SESSION
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null);

    /**
     * Put a key / value pair or array of key / value pairs in the session.
	 * 推入键值入SESSION按键值对
     *
     * @param  string|array  $key
     * @param  mixed  $value
     * @return void
     */
    public function put($key, $value = null);

    /**
     * Get the CSRF token value.
	 * 得到CSRF token值
     *
     * @return string
     */
    public function token();

    /**
     * Remove an item from the session, returning its value.
	 * 移除项从SESSION中
     *
     * @param  string  $key
     * @return mixed
     */
    public function remove($key);

    /**
     * Remove one or many items from the session.
	 * 移除多个项从SESSION中
     *
     * @param  string|array  $keys
     * @return void
     */
    public function forget($keys);

    /**
     * Remove all of the items from the session.
	 * 清空SESSION，移除所有项从SESSION中
     *
     * @return void
     */
    public function flush();

    /**
     * Generate a new session ID for the session.
	 * 生成一个新的SESSIONID
     *
     * @param  bool  $destroy
     * @return bool
     */
    public function migrate($destroy = false);

    /**
     * Determine if the session has been started.
	 * 确定是否SESSION已经开始
     *
     * @return bool
     */
    public function isStarted();

    /**
     * Get the previous URL from the session.
	 * 从会话中获得前一个URL
     *
     * @return string|null
     */
    public function previousUrl();

    /**
     * Set the "previous" URL in the session.
	 * 设置前一个URl在SESSION中
     *
     * @param  string  $url
     * @return void
     */
    public function setPreviousUrl($url);

    /**
     * Get the session handler instance.
	 * 得到SESSION处理实例
     *
     * @return \SessionHandlerInterface
     */
    public function getHandler();

    /**
     * Determine if the session handler needs a request.
	 * 确定是否SESSION处理需要请求
     *
     * @return bool
     */
    public function handlerNeedsRequest();

    /**
     * Set the request on the handler instance.
	 * 设置请求处理实例
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function setRequestOnHandler($request);
}
