<?php
/**
 * Session存储
 */

namespace Illuminate\Session;

use Closure;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use SessionHandlerInterface;
use stdClass;

class Store implements Session
{
    /**
     * The session ID.
	 * 会话ID 
     *
     * @var string
     */
    protected $id;

    /**
     * The session name.
	 * 会话名
     *
     * @var string
     */
    protected $name;

    /**
     * The session attributes.
	 * 会话属性
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * The session handler implementation.
	 * 会话处理程序实现
     *
     * @var \SessionHandlerInterface
     */
    protected $handler;

    /**
     * Session store started status.
	 * 会话存储启动状态
     *
     * @var bool
     */
    protected $started = false;

    /**
     * Create a new session instance.
	 * 创建新的会话实例
     *
     * @param  string  $name
     * @param  \SessionHandlerInterface  $handler
     * @param  string|null  $id
     * @return void
     */
    public function __construct($name, SessionHandlerInterface $handler, $id = null)
    {
        $this->setId($id);
        $this->name = $name;
        $this->handler = $handler;
    }

    /**
     * Start the session, reading the data from a handler.
	 * 启动会话，从处理程序读取数据。
     *
     * @return bool
     */
    public function start()
    {
        $this->loadSession();

        if (! $this->has('_token')) {
            $this->regenerateToken();
        }

        return $this->started = true;
    }

    /**
     * Load the session data from the handler.
	 * 加载会话数据从处理程序
     *
     * @return void
     */
    protected function loadSession()
    {
        $this->attributes = array_merge($this->attributes, $this->readFromHandler());
    }

    /**
     * Read the session data from the handler.
	 * 读取会话数据从处理程序
     *
     * @return array
     */
    protected function readFromHandler()
    {
        if ($data = $this->handler->read($this->getId())) {
            $data = @unserialize($this->prepareForUnserialize($data));

            if ($data !== false && ! is_null($data) && is_array($data)) {
                return $data;
            }
        }

        return [];
    }

    /**
     * Prepare the raw string data from the session for unserialization.
	 * 准备来自会话的原始字符串数据以进行反序列化
     *
     * @param  string  $data
     * @return string
     */
    protected function prepareForUnserialize($data)
    {
        return $data;
    }

    /**
     * Save the session data to storage.
	 * 保存会话数据至存储
     *
     * @return void
     */
    public function save()
    {
        $this->ageFlashData();

        $this->handler->write($this->getId(), $this->prepareForStorage(
            serialize($this->attributes)
        ));

        $this->started = false;
    }

    /**
     * Prepare the serialized session data for storage.
	 * 准备序列化的会话数据进行存储
     *
     * @param  string  $data
     * @return string
     */
    protected function prepareForStorage($data)
    {
        return $data;
    }

    /**
     * Age the flash data for the session.
	 * 将会话的flash数据老化
     *
     * @return void
     */
    public function ageFlashData()
    {
        $this->forget($this->get('_flash.old', []));

        $this->put('_flash.old', $this->get('_flash.new', []));

        $this->put('_flash.new', []);
    }

    /**
     * Get all of the session data.
	 * 得到所有会话数据
     *
     * @return array
     */
    public function all()
    {
        return $this->attributes;
    }

    /**
     * Get a subset of the session data.
	 * 得到会话数据的子集
     *
     * @param  array  $keys
     * @return array
     */
    public function only(array $keys)
    {
        return Arr::only($this->attributes, $keys);
    }

    /**
     * Checks if a key exists.
	 * 检查是否存在密钥
     *
     * @param  string|array  $key
     * @return bool
     */
    public function exists($key)
    {
        $placeholder = new stdClass;

        return ! collect(is_array($key) ? $key : func_get_args())->contains(function ($key) use ($placeholder) {
            return $this->get($key, $placeholder) === $placeholder;
        });
    }

    /**
     * Checks if a key is present and not null.
	 * 检查键是否存在且不为空
     *
     * @param  string|array  $key
     * @return bool
     */
    public function has($key)
    {
        return ! collect(is_array($key) ? $key : func_get_args())->contains(function ($key) {
            return is_null($this->get($key));
        });
    }

    /**
     * Get an item from the session.
	 * 得到一个项目从会话中
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return Arr::get($this->attributes, $key, $default);
    }

    /**
     * Get the value of a given key and then forget it.
	 * 得到给定键的值，然后忘记它。
     *
     * @param  string  $key
     * @param  string|null  $default
     * @return mixed
     */
    public function pull($key, $default = null)
    {
        return Arr::pull($this->attributes, $key, $default);
    }

    /**
     * Determine if the session contains old input.
	 * 确定会话是否包含旧的输入
     *
     * @param  string|null  $key
     * @return bool
     */
    public function hasOldInput($key = null)
    {
        $old = $this->getOldInput($key);

        return is_null($key) ? count($old) > 0 : ! is_null($old);
    }

    /**
     * Get the requested item from the flashed input array.
	 * 得到请求的项从闪过的输入数组中
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function getOldInput($key = null, $default = null)
    {
        return Arr::get($this->get('_old_input', []), $key, $default);
    }

    /**
     * Replace the given session attributes entirely.
	 * 完全替换给定的会话属性
     *
     * @param  array  $attributes
     * @return void
     */
    public function replace(array $attributes)
    {
        $this->put($attributes);
    }

    /**
     * Put a key / value pair or array of key / value pairs in the session.
	 * 在会话中放入一个键/值对或键/值对数组
     *
     * @param  string|array  $key
     * @param  mixed  $value
     * @return void
     */
    public function put($key, $value = null)
    {
        if (! is_array($key)) {
            $key = [$key => $value];
        }

        foreach ($key as $arrayKey => $arrayValue) {
            Arr::set($this->attributes, $arrayKey, $arrayValue);
        }
    }

    /**
     * Get an item from the session, or store the default value.
	 * 从会话中获取项，或存储默认值。
     *
     * @param  string  $key
     * @param  \Closure  $callback
     * @return mixed
     */
    public function remember($key, Closure $callback)
    {
        if (! is_null($value = $this->get($key))) {
            return $value;
        }

        return tap($callback(), function ($value) use ($key) {
            $this->put($key, $value);
        });
    }

    /**
     * Push a value onto a session array.
	 * 将值压入会话数组
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function push($key, $value)
    {
        $array = $this->get($key, []);

        $array[] = $value;

        $this->put($key, $array);
    }

    /**
     * Increment the value of an item in the session.
	 * 增加会话中某项的值
     *
     * @param  string  $key
     * @param  int  $amount
     * @return mixed
     */
    public function increment($key, $amount = 1)
    {
        $this->put($key, $value = $this->get($key, 0) + $amount);

        return $value;
    }

    /**
     * Decrement the value of an item in the session.
	 * 递减会话中某项的值
     *
     * @param  string  $key
     * @param  int  $amount
     * @return int
     */
    public function decrement($key, $amount = 1)
    {
        return $this->increment($key, $amount * -1);
    }

    /**
     * Flash a key / value pair to the session.
	 * 将一个键/值对Flash到会话中
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function flash(string $key, $value = true)
    {
        $this->put($key, $value);

        $this->push('_flash.new', $key);

        $this->removeFromOldFlashData([$key]);
    }

    /**
     * Flash a key / value pair to the session for immediate use.
	 * 将一个键/值对保存到会话中以便立即使用
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function now($key, $value)
    {
        $this->put($key, $value);

        $this->push('_flash.old', $key);
    }

    /**
     * Reflash all of the session flash data.
	 * 刷新所有会话刷新数据
     *
     * @return void
     */
    public function reflash()
    {
        $this->mergeNewFlashes($this->get('_flash.old', []));

        $this->put('_flash.old', []);
    }

    /**
     * Reflash a subset of the current flash data.
	 * 刷新当前flash数据的子集
     *
     * @param  array|mixed  $keys
     * @return void
     */
    public function keep($keys = null)
    {
        $this->mergeNewFlashes($keys = is_array($keys) ? $keys : func_get_args());

        $this->removeFromOldFlashData($keys);
    }

    /**
     * Merge new flash keys into the new flash array.
	 * 将新的flash key合并到新的flash array中
     *
     * @param  array  $keys
     * @return void
     */
    protected function mergeNewFlashes(array $keys)
    {
        $values = array_unique(array_merge($this->get('_flash.new', []), $keys));

        $this->put('_flash.new', $values);
    }

    /**
     * Remove the given keys from the old flash data.
	 * 从旧的闪存数据中删除给定的键
     *
     * @param  array  $keys
     * @return void
     */
    protected function removeFromOldFlashData(array $keys)
    {
        $this->put('_flash.old', array_diff($this->get('_flash.old', []), $keys));
    }

    /**
     * Flash an input array to the session.
	 * 将输入数组刷新到会话
     *
     * @param  array  $value
     * @return void
     */
    public function flashInput(array $value)
    {
        $this->flash('_old_input', $value);
    }

    /**
     * Remove an item from the session, returning its value.
	 * 从会话中删除项，返回其值。
     *
     * @param  string  $key
     * @return mixed
     */
    public function remove($key)
    {
        return Arr::pull($this->attributes, $key);
    }

    /**
     * Remove one or many items from the session.
	 * 从会话中删除一个或多个项目。
     *
     * @param  string|array  $keys
     * @return void
     */
    public function forget($keys)
    {
        Arr::forget($this->attributes, $keys);
    }

    /**
     * Remove all of the items from the session.
	 * 从会话中删除所有项
     *
     * @return void
     */
    public function flush()
    {
        $this->attributes = [];
    }

    /**
     * Flush the session data and regenerate the ID.
	 * 刷新会话数据并重新生成ID
     *
     * @return bool
     */
    public function invalidate()
    {
        $this->flush();

        return $this->migrate(true);
    }

    /**
     * Generate a new session identifier.
	 * 生成新的会话标识符
     *
     * @param  bool  $destroy
     * @return bool
     */
    public function regenerate($destroy = false)
    {
        return tap($this->migrate($destroy), function () {
            $this->regenerateToken();
        });
    }

    /**
     * Generate a new session ID for the session.
	 * 生成一个新的会话ID为会话
     *
     * @param  bool  $destroy
     * @return bool
     */
    public function migrate($destroy = false)
    {
        if ($destroy) {
            $this->handler->destroy($this->getId());
        }

        $this->setExists(false);

        $this->setId($this->generateSessionId());

        return true;
    }

    /**
     * Determine if the session has been started.
	 * 确定会话是否已启动
     *
     * @return bool
     */
    public function isStarted()
    {
        return $this->started;
    }

    /**
     * Get the name of the session.
	 * 得到会话的名称
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the name of the session.
	 * 设置会话名称
     *
     * @param  string  $name
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get the current session ID.
	 * 得到当前会话ID
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the session ID.
	 * 设置会话ID
     *
     * @param  string  $id
     * @return void
     */
    public function setId($id)
    {
        $this->id = $this->isValidId($id) ? $id : $this->generateSessionId();
    }

    /**
     * Determine if this is a valid session ID.
	 * 确定这是否是有效的会话ID
     *
     * @param  string  $id
     * @return bool
     */
    public function isValidId($id)
    {
        return is_string($id) && ctype_alnum($id) && strlen($id) === 40;
    }

    /**
     * Get a new, random session ID.
	 * 得到一个新的随机的会话ID
     *
     * @return string
     */
    protected function generateSessionId()
    {
        return Str::random(40);
    }

    /**
     * Set the existence of the session on the handler if applicable.
	 * 如果适用，设置会话的存在性在处理程序上。
     *
     * @param  bool  $value
     * @return void
     */
    public function setExists($value)
    {
        if ($this->handler instanceof ExistenceAwareInterface) {
            $this->handler->setExists($value);
        }
    }

    /**
     * Get the CSRF token value.
	 * 得到CSRF令牌值
     *
     * @return string
     */
    public function token()
    {
        return $this->get('_token');
    }

    /**
     * Regenerate the CSRF token value.
	 * 重新生成CSRF令牌值
     *
     * @return void
     */
    public function regenerateToken()
    {
        $this->put('_token', Str::random(40));
    }

    /**
     * Get the previous URL from the session.
	 * 得到前一个URL从会话中
     *
     * @return string|null
     */
    public function previousUrl()
    {
        return $this->get('_previous.url');
    }

    /**
     * Set the "previous" URL in the session.
	 * 设置会话中的"前一个"URL
     *
     * @param  string  $url
     * @return void
     */
    public function setPreviousUrl($url)
    {
        $this->put('_previous.url', $url);
    }

    /**
     * Get the underlying session handler implementation.
	 * 得到底层会话处理程序实现
     *
     * @return \SessionHandlerInterface
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * Determine if the session handler needs a request.
	 * 确定会话处理程序是否需要请求
     *
     * @return bool
     */
    public function handlerNeedsRequest()
    {
        return $this->handler instanceof CookieSessionHandler;
    }

    /**
     * Set the request on the handler instance.
	 * 设置请求在处理程序实例上
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function setRequestOnHandler($request)
    {
        if ($this->handlerNeedsRequest()) {
            $this->handler->setRequest($request);
        }
    }
}
