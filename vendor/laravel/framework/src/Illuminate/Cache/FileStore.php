<?php
/**
 * 缓存文件存储
 */

namespace Illuminate\Cache;

use Exception;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\InteractsWithTime;

class FileStore implements Store
{
    use InteractsWithTime, RetrievesMultipleKeys;

    /**
     * The Illuminate Filesystem instance.
	 * 文件系统实例
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The file cache directory.
	 * 文件缓存目录
     *
     * @var string
     */
    protected $directory;

    /**
     * Octal representation of the cache file permissions.
	 * 缓存文件权限的八进制表示
     *
     * @var int|null
     */
    protected $filePermission;

    /**
     * Create a new file cache store instance.
	 * 创建新的文件缓存存储实例
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  string  $directory
     * @param  int|null  $filePermission
     * @return void
     */
    public function __construct(Filesystem $files, $directory, $filePermission = null)
    {
        $this->files = $files;
        $this->directory = $directory;
        $this->filePermission = $filePermission;
    }

    /**
     * Retrieve an item from the cache by key.
	 * 检索项目从缓存中
     *
     * @param  string|array  $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->getPayload($key)['data'] ?? null;
    }

    /**
     * Store an item in the cache for a given number of seconds.
	 * 将项目存入缓存中使用给定秒数
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  int  $seconds
     * @return bool
     */
    public function put($key, $value, $seconds)
    {
        $this->ensureCacheDirectoryExists($path = $this->path($key));

        $result = $this->files->put(
            $path, $this->expiration($seconds).serialize($value), true
        );

        if ($result !== false && $result > 0) {
            $this->ensureFileHasCorrectPermissions($path);

            return true;
        }

        return false;
    }

    /**
     * Create the file cache directory if necessary.
	 * 创建文件缓存目录
     *
     * @param  string  $path
     * @return void
     */
    protected function ensureCacheDirectoryExists($path)
    {
        if (! $this->files->exists(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }
    }

    /**
     * Ensure the cache file has the correct permissions.
	 * 确保缓存文件有正确的权限
     *
     * @param  string  $path
     * @return void
     */
    protected function ensureFileHasCorrectPermissions($path)
    {
        if (is_null($this->filePermission) ||
            intval($this->files->chmod($path), 8) == $this->filePermission) {
            return;
        }

        $this->files->chmod($path, $this->filePermission);
    }

    /**
     * Increment the value of an item in the cache.
	 * 增加缓存中项的值
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int
     */
    public function increment($key, $value = 1)
    {
        $raw = $this->getPayload($key);

        return tap(((int) $raw['data']) + $value, function ($newValue) use ($key, $raw) {
            $this->put($key, $newValue, $raw['time'] ?? 0);
        });
    }

    /**
     * Decrement the value of an item in the cache.
	 * 递减缓存中项的值
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int
     */
    public function decrement($key, $value = 1)
    {
        return $this->increment($key, $value * -1);
    }

    /**
     * Store an item in the cache indefinitely.
	 * 存储项无限期地在缓存中
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return bool
     */
    public function forever($key, $value)
    {
        return $this->put($key, $value, 0);
    }

    /**
     * Remove an item from the cache.
	 * 移除单个缓存项目
     *
     * @param  string  $key
     * @return bool
     */
    public function forget($key)
    {
        if ($this->files->exists($file = $this->path($key))) {
            return $this->files->delete($file);
        }

        return false;
    }

    /**
     * Remove all items from the cache.
	 * 移除所有缓存项目
     *
     * @return bool
     */
    public function flush()
    {
        if (! $this->files->isDirectory($this->directory)) {
            return false;
        }

        foreach ($this->files->directories($this->directory) as $directory) {
            $deleted = $this->files->deleteDirectory($directory);

            if (! $deleted || $this->files->exists($directory)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Retrieve an item and expiry time from the cache by key.
	 * 检索项目和过期时间按键从缓存中
     *
     * @param  string  $key
     * @return array
     */
    protected function getPayload($key)
    {
        $path = $this->path($key);

        // If the file doesn't exist, we obviously cannot return the cache so we will
        // just return null. Otherwise, we'll get the contents of the file and get
        // the expiration UNIX timestamps from the start of the file's contents.
		// 如果文件不存在，我们显示不能返回缓存，因此我们只返回NULl。
		// 否则，我们将获取文件的内容并获取从文件内容开始的到期UNIX时间戳。
        try {
            $expire = substr(
                $contents = $this->files->get($path, true), 0, 10
            );
        } catch (Exception $e) {
            return $this->emptyPayload();
        }

        // If the current time is greater than expiration timestamps we will delete
        // the file and return null. This helps clean up the old files and keeps
        // this directory much cleaner for us as old files aren't hanging out.
		// 如果当前时间大于过期时间戳，我们将删除文件并返回NULL。
		// 这有助于清理旧文件并保持这个目录对我们来说要干净得多，因为旧文件不会挂在外面。
        if ($this->currentTime() >= $expire) {
            $this->forget($key);

            return $this->emptyPayload();
        }

        try {
            $data = unserialize(substr($contents, 10));
        } catch (Exception $e) {
            $this->forget($key);

            return $this->emptyPayload();
        }

        // Next, we'll extract the number of seconds that are remaining for a cache
        // so that we can properly retain the time for things like the increment
        // operation that may be performed on this cache on a later operation.
		// 接下来，我们将提取缓存剩余秒数，
		// 这样我们就可以正确地保留时间来处理增量等事情。
		// 以致可以在以后的操作中对此缓存执行操作。
        $time = $expire - $this->currentTime();

        return compact('data', 'time');
    }

    /**
     * Get a default empty payload for the cache.
	 * 得到缓存的默认空有效负载
     *
     * @return array
     */
    protected function emptyPayload()
    {
        return ['data' => null, 'time' => null];
    }

    /**
     * Get the full path for the given cache key.
	 * 得到给定缓存键的完整路径
     *
     * @param  string  $key
     * @return string
     */
    protected function path($key)
    {
        $parts = array_slice(str_split($hash = sha1($key), 2), 0, 2);

        return $this->directory.'/'.implode('/', $parts).'/'.$hash;
    }

    /**
     * Get the expiration time based on the given seconds.
	 * 根据给定的秒获取过期时间
     *
     * @param  int  $seconds
     * @return int
     */
    protected function expiration($seconds)
    {
        $time = $this->availableAt($seconds);

        return $seconds === 0 || $time > 9999999999 ? 9999999999 : $time;
    }

    /**
     * Get the Filesystem instance.
	 * 得到文件系统实例
     *
     * @return \Illuminate\Filesystem\Filesystem
     */
    public function getFilesystem()
    {
        return $this->files;
    }

    /**
     * Get the working directory of the cache.
	 * 得到缓存的工作目录
     *
     * @return string
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * Get the cache key prefix.
	 * 得到缓存前缀
     *
     * @return string
     */
    public function getPrefix()
    {
        return '';
    }
}
