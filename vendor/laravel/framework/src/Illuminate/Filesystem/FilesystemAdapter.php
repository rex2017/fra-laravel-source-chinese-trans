<?php
/**
 * 文件系统适配器
 */

namespace Illuminate\Filesystem;

use Illuminate\Contracts\Filesystem\Cloud as CloudFilesystemContract;
use Illuminate\Contracts\Filesystem\FileExistsException as ContractFileExistsException;
use Illuminate\Contracts\Filesystem\FileNotFoundException as ContractFileNotFoundException;
use Illuminate\Contracts\Filesystem\Filesystem as FilesystemContract;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use League\Flysystem\Adapter\Ftp;
use League\Flysystem\Adapter\Local as LocalAdapter;
use League\Flysystem\AdapterInterface;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Cached\CachedAdapter;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use PHPUnit\Framework\Assert as PHPUnit;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @mixin \League\Flysystem\FilesystemInterface
 */
class FilesystemAdapter implements CloudFilesystemContract
{
    /**
     * The Flysystem filesystem implementation.
	 * Flysystem文件系统实现
     *
     * @var \League\Flysystem\FilesystemInterface
     */
    protected $driver;

    /**
     * Create a new filesystem adapter instance.
	 * 创建新的文件适配器实例
     *
     * @param  \League\Flysystem\FilesystemInterface  $driver
     * @return void
     */
    public function __construct(FilesystemInterface $driver)
    {
        $this->driver = $driver;
    }

    /**
     * Assert that the given file exists.
	 * 声明文件路径是否存在
     *
     * @param  string|array  $path
     * @return $this
     */
    public function assertExists($path)
    {
        $paths = Arr::wrap($path);

        foreach ($paths as $path) {
            PHPUnit::assertTrue(
                $this->exists($path), "Unable to find a file at path [{$path}]."
            );
        }

        return $this;
    }

    /**
     * Assert that the given file does not exist.
	 * 声明文件不存在
     *
     * @param  string|array  $path
     * @return $this
     */
    public function assertMissing($path)
    {
        $paths = Arr::wrap($path);

        foreach ($paths as $path) {
            PHPUnit::assertFalse(
                $this->exists($path), "Found unexpected file at path [{$path}]."
            );
        }

        return $this;
    }

    /**
     * Determine if a file exists.
	 * 确定是否一个文件存在
     *
     * @param  string  $path
     * @return bool
     */
    public function exists($path)
    {
        return $this->driver->has($path);
    }

    /**
     * Determine if a file or directory is missing.
	 * 确定是否文件或目录丢失
     *
     * @param  string  $path
     * @return bool
     */
    public function missing($path)
    {
        return ! $this->exists($path);
    }

    /**
     * Get the full path for the file at the given "short" path.
	 * 在给定的"短"路径处获取文件的完整路径
     *
     * @param  string  $path
     * @return string
     */
    public function path($path)
    {
        return $this->driver->getAdapter()->getPathPrefix().$path;
    }

    /**
     * Get the contents of a file.
	 * 得到文件内容
     *
     * @param  string  $path
     * @return string
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function get($path)
    {
        try {
            return $this->driver->read($path);
        } catch (FileNotFoundException $e) {
            throw new ContractFileNotFoundException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Create a streamed response for a given file.
	 * 创建流响应为给定文件
     *
     * @param  string  $path
     * @param  string|null  $name
     * @param  array|null  $headers
     * @param  string|null  $disposition
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function response($path, $name = null, array $headers = [], $disposition = 'inline')
    {
        $response = new StreamedResponse;

        $filename = $name ?? basename($path);

        $disposition = $response->headers->makeDisposition(
            $disposition, $filename, $this->fallbackName($filename)
        );

        $response->headers->replace($headers + [
            'Content-Type' => $this->mimeType($path),
            'Content-Length' => $this->size($path),
            'Content-Disposition' => $disposition,
        ]);

        $response->setCallback(function () use ($path) {
            $stream = $this->readStream($path);
            fpassthru($stream);
            fclose($stream);
        });

        return $response;
    }

    /**
     * Create a streamed download response for a given file.
	 * 创建流下载响应为给定文件
     *
     * @param  string  $path
     * @param  string|null  $name
     * @param  array|null  $headers
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function download($path, $name = null, array $headers = [])
    {
        return $this->response($path, $name, $headers, 'attachment');
    }

    /**
     * Convert the string to ASCII characters that are equivalent to the given name.
	 * 将字符串转换为与给定名称等效的ASCII字符
     *
     * @param  string  $name
     * @return string
     */
    protected function fallbackName($name)
    {
        return str_replace('%', '', Str::ascii($name));
    }

    /**
     * Write the contents of a file.
	 * 写入文件的内容
     *
     * @param  string  $path
     * @param  string|resource  $contents
     * @param  mixed  $options
     * @return bool
     */
    public function put($path, $contents, $options = [])
    {
        $options = is_string($options)
                     ? ['visibility' => $options]
                     : (array) $options;

        // If the given contents is actually a file or uploaded file instance than we will
        // automatically store the file using a stream. This provides a convenient path
        // for the developer to store streams without managing them manually in code.
		// 如果给定的内容实际上是一个文件或上传的文件实例，那么我们将使用流自动存储文件。
		// 这提供了一条便捷的路径给开发人员存储流，而无需在代码中手动管理。
        if ($contents instanceof File ||
            $contents instanceof UploadedFile) {
            return $this->putFile($path, $contents, $options);
        }

        if ($contents instanceof StreamInterface) {
            return $this->driver->putStream($path, $contents->detach(), $options);
        }

        return is_resource($contents)
                ? $this->driver->putStream($path, $contents, $options)
                : $this->driver->put($path, $contents, $options);
    }

    /**
     * Store the uploaded file on the disk.
	 * 将上传的文件存储在磁盘上
     *
     * @param  string  $path
     * @param  \Illuminate\Http\File|\Illuminate\Http\UploadedFile|string  $file
     * @param  mixed  $options
     * @return string|false
     */
    public function putFile($path, $file, $options = [])
    {
        $file = is_string($file) ? new File($file) : $file;

        return $this->putFileAs($path, $file, $file->hashName(), $options);
    }

    /**
     * Store the uploaded file on the disk with a given name.
	 * 将上传的文件以给定的名称存储在磁盘上
     *
     * @param  string  $path
     * @param  \Illuminate\Http\File|\Illuminate\Http\UploadedFile|string  $file
     * @param  string  $name
     * @param  mixed  $options
     * @return string|false
     */
    public function putFileAs($path, $file, $name, $options = [])
    {
        $stream = fopen(is_string($file) ? $file : $file->getRealPath(), 'r');

        // Next, we will format the path of the file and store the file using a stream since
        // they provide better performance than alternatives. Once we write the file this
        // stream will get closed automatically by us so the developer doesn't have to.
		// 下一步，我们将格式化文件的路径并使用流存储文件，因为它们提供了替代品更好的性能。
		// 一旦我们写了这个文件流将由我们自动关闭，因此开发人员不必这样做。
        $result = $this->put(
            $path = trim($path.'/'.$name, '/'), $stream, $options
        );

        if (is_resource($stream)) {
            fclose($stream);
        }

        return $result ? $path : false;
    }

    /**
     * Get the visibility for the given path.
	 * 得到给定路径的可见性
     *
     * @param  string  $path
     * @return string
     */
    public function getVisibility($path)
    {
        if ($this->driver->getVisibility($path) == AdapterInterface::VISIBILITY_PUBLIC) {
            return FilesystemContract::VISIBILITY_PUBLIC;
        }

        return FilesystemContract::VISIBILITY_PRIVATE;
    }

    /**
     * Set the visibility for the given path.
	 * 设置给定路径的可见性
     *
     * @param  string  $path
     * @param  string  $visibility
     * @return bool
     */
    public function setVisibility($path, $visibility)
    {
        return $this->driver->setVisibility($path, $this->parseVisibility($visibility));
    }

    /**
     * Prepend to a file.
	 * 添加至文件中
     *
     * @param  string  $path
     * @param  string  $data
     * @param  string  $separator
     * @return bool
     */
    public function prepend($path, $data, $separator = PHP_EOL)
    {
        if ($this->exists($path)) {
            return $this->put($path, $data.$separator.$this->get($path));
        }

        return $this->put($path, $data);
    }

    /**
     * Append to a file.
	 * 追加至文件中
     *
     * @param  string  $path
     * @param  string  $data
     * @param  string  $separator
     * @return bool
     */
    public function append($path, $data, $separator = PHP_EOL)
    {
        if ($this->exists($path)) {
            return $this->put($path, $this->get($path).$separator.$data);
        }

        return $this->put($path, $data);
    }

    /**
     * Delete the file at a given path.
	 * 删除指定路径下的文件
     *
     * @param  string|array  $paths
     * @return bool
     */
    public function delete($paths)
    {
        $paths = is_array($paths) ? $paths : func_get_args();

        $success = true;

        foreach ($paths as $path) {
            try {
                if (! $this->driver->delete($path)) {
                    $success = false;
                }
            } catch (FileNotFoundException $e) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Copy a file to a new location.
	 * 复制文件至新位置
     *
     * @param  string  $from
     * @param  string  $to
     * @return bool
     */
    public function copy($from, $to)
    {
        return $this->driver->copy($from, $to);
    }

    /**
     * Move a file to a new location.
	 * 移动文件至新位置
     *
     * @param  string  $from
     * @param  string  $to
     * @return bool
     */
    public function move($from, $to)
    {
        return $this->driver->rename($from, $to);
    }

    /**
     * Get the file size of a given file.
	 * 得到文件大小 
     *
     * @param  string  $path
     * @return int
     */
    public function size($path)
    {
        return $this->driver->getSize($path);
    }

    /**
     * Get the mime-type of a given file.
	 * 获取给定文件的mime类型
     *
     * @param  string  $path
     * @return string|false
     */
    public function mimeType($path)
    {
        return $this->driver->getMimetype($path);
    }

    /**
     * Get the file's last modification time.
	 * 得到文件的最后修改时间
     *
     * @param  string  $path
     * @return int
     */
    public function lastModified($path)
    {
        return $this->driver->getTimestamp($path);
    }

    /**
     * Get the URL for the file at the given path.
	 * 得到文件URL从给定路径
     *
     * @param  string  $path
     * @return string
     *
     * @throws \RuntimeException
     */
    public function url($path)
    {
        $adapter = $this->driver->getAdapter();

        if ($adapter instanceof CachedAdapter) {
            $adapter = $adapter->getAdapter();
        }

        if (method_exists($adapter, 'getUrl')) {
            return $adapter->getUrl($path);
        } elseif (method_exists($this->driver, 'getUrl')) {
            return $this->driver->getUrl($path);
        } elseif ($adapter instanceof AwsS3Adapter) {
            return $this->getAwsUrl($adapter, $path);
        } elseif ($adapter instanceof Ftp) {
            return $this->getFtpUrl($path);
        } elseif ($adapter instanceof LocalAdapter) {
            return $this->getLocalUrl($path);
        } else {
            throw new RuntimeException('This driver does not support retrieving URLs.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function readStream($path)
    {
        try {
            return $this->driver->readStream($path) ?: null;
        } catch (FileNotFoundException $e) {
            throw new ContractFileNotFoundException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function writeStream($path, $resource, array $options = [])
    {
        try {
            return $this->driver->writeStream($path, $resource, $options);
        } catch (FileExistsException $e) {
            throw new ContractFileExistsException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Get the URL for the file at the given path.
	 * 得到给定路径下文件的URL
     *
     * @param  \League\Flysystem\AwsS3v3\AwsS3Adapter  $adapter
     * @param  string  $path
     * @return string
     */
    protected function getAwsUrl($adapter, $path)
    {
        // If an explicit base URL has been set on the disk configuration then we will use
        // it as the base URL instead of the default path. This allows the developer to
        // have full control over the base path for this filesystem's generated URLs.
		// 如果在磁盘配置上设置了显式的基本URL，那么我们将使用它作为基本URL，而不是默认路径。
		// 这允许开发人员完全控制此文件系统生成的URL的基本路径。
        if (! is_null($url = $this->driver->getConfig()->get('url'))) {
            return $this->concatPathToUrl($url, $adapter->getPathPrefix().$path);
        }

        return $adapter->getClient()->getObjectUrl(
            $adapter->getBucket(), $adapter->getPathPrefix().$path
        );
    }

    /**
     * Get the URL for the file at the given path.
	 * 得到文件的URL在给定路径下
     *
     * @param  string  $path
     * @return string
     */
    protected function getFtpUrl($path)
    {
        $config = $this->driver->getConfig();

        return $config->has('url')
                ? $this->concatPathToUrl($config->get('url'), $path)
                : $path;
    }

    /**
     * Get the URL for the file at the given path.
	 * 得到给定路径下文件的URL
     *
     * @param  string  $path
     * @return string
     */
    protected function getLocalUrl($path)
    {
        $config = $this->driver->getConfig();

        // If an explicit base URL has been set on the disk configuration then we will use
        // it as the base URL instead of the default path. This allows the developer to
        // have full control over the base path for this filesystem's generated URLs.
		// 如果在磁盘配置上设置了显式的基本URL，那么我们将使用它作为基本URL，而不是默认路径。
		// 这允许开发人员完全控制此文件系统生成的URL的基本路径。
        if ($config->has('url')) {
            return $this->concatPathToUrl($config->get('url'), $path);
        }

        $path = '/storage/'.$path;

        // If the path contains "storage/public", it probably means the developer is using
        // the default disk to generate the path instead of the "public" disk like they
        // are really supposed to use. We will remove the public from this path here.
		// 如果路径包含"storage/public"，这可能意味着开发人员正在使用默认磁盘生成路径，
		// 而不是像他们真正应该使用的那样使用“public”磁盘。我们将把公众从这条路上赶走。
        if (Str::contains($path, '/storage/public/')) {
            return Str::replaceFirst('/public/', '/', $path);
        }

        return $path;
    }

    /**
     * Get a temporary URL for the file at the given path.
	 * 得到给定路径下文件的临时URL
     *
     * @param  string  $path
     * @param  \DateTimeInterface  $expiration
     * @param  array  $options
     * @return string
     *
     * @throws \RuntimeException
     */
    public function temporaryUrl($path, $expiration, array $options = [])
    {
        $adapter = $this->driver->getAdapter();

        if ($adapter instanceof CachedAdapter) {
            $adapter = $adapter->getAdapter();
        }

        if (method_exists($adapter, 'getTemporaryUrl')) {
            return $adapter->getTemporaryUrl($path, $expiration, $options);
        } elseif ($adapter instanceof AwsS3Adapter) {
            return $this->getAwsTemporaryUrl($adapter, $path, $expiration, $options);
        } else {
            throw new RuntimeException('This driver does not support creating temporary URLs.');
        }
    }

    /**
     * Get a temporary URL for the file at the given path.
	 * 得到给定路径下文件的临时URL
     *
     * @param  \League\Flysystem\AwsS3v3\AwsS3Adapter  $adapter
     * @param  string  $path
     * @param  \DateTimeInterface  $expiration
     * @param  array  $options
     * @return string
     */
    public function getAwsTemporaryUrl($adapter, $path, $expiration, $options)
    {
        $client = $adapter->getClient();

        $command = $client->getCommand('GetObject', array_merge([
            'Bucket' => $adapter->getBucket(),
            'Key' => $adapter->getPathPrefix().$path,
        ], $options));

        return (string) $client->createPresignedRequest(
            $command, $expiration
        )->getUri();
    }

    /**
     * Concatenate a path to a URL.
	 * 连接路径到URL
     *
     * @param  string  $url
     * @param  string  $path
     * @return string
     */
    protected function concatPathToUrl($url, $path)
    {
        return rtrim($url, '/').'/'.ltrim($path, '/');
    }

    /**
     * Get an array of all files in a directory.
	 * 得到目录中所有文件的数组
     *
     * @param  string|null  $directory
     * @param  bool  $recursive
     * @return array
     */
    public function files($directory = null, $recursive = false)
    {
        $contents = $this->driver->listContents($directory, $recursive);

        return $this->filterContentsByType($contents, 'file');
    }

    /**
     * Get all of the files from the given directory (recursive).
	 * 得到所有文件从给定目录
     *
     * @param  string|null  $directory
     * @return array
     */
    public function allFiles($directory = null)
    {
        return $this->files($directory, true);
    }

    /**
     * Get all of the directories within a given directory.
	 * 得到给定的所有目录
     *
     * @param  string|null  $directory
     * @param  bool  $recursive
     * @return array
     */
    public function directories($directory = null, $recursive = false)
    {
        $contents = $this->driver->listContents($directory, $recursive);

        return $this->filterContentsByType($contents, 'dir');
    }

    /**
     * Get all (recursive) of the directories within a given directory.
	 * 得到给定目录中的所有(递归)目录
     *
     * @param  string|null  $directory
     * @return array
     */
    public function allDirectories($directory = null)
    {
        return $this->directories($directory, true);
    }

    /**
     * Create a directory.
	 * 创建目录
     *
     * @param  string  $path
     * @return bool
     */
    public function makeDirectory($path)
    {
        return $this->driver->createDir($path);
    }

    /**
     * Recursively delete a directory.
	 * 递归删除目录
     *
     * @param  string  $directory
     * @return bool
     */
    public function deleteDirectory($directory)
    {
        return $this->driver->deleteDir($directory);
    }

    /**
     * Flush the Flysystem cache.
	 * 刷新Flysystem缓存
     *
     * @return void
     */
    public function flushCache()
    {
        $adapter = $this->driver->getAdapter();

        if ($adapter instanceof CachedAdapter) {
            $adapter->getCache()->flush();
        }
    }

    /**
     * Get the Flysystem driver.
	 * 得到Flysystem驱动
     *
     * @return \League\Flysystem\FilesystemInterface
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * Filter directory contents by type.
	 * 筛选目录内容按类型
     *
     * @param  array  $contents
     * @param  string  $type
     * @return array
     */
    protected function filterContentsByType($contents, $type)
    {
        return Collection::make($contents)
            ->where('type', $type)
            ->pluck('path')
            ->values()
            ->all();
    }

    /**
     * Parse the given visibility value.
	 * 解析给定的可见性值
     *
     * @param  string|null  $visibility
     * @return string|null
     *
     * @throws \InvalidArgumentException
     */
    protected function parseVisibility($visibility)
    {
        if (is_null($visibility)) {
            return;
        }

        switch ($visibility) {
            case FilesystemContract::VISIBILITY_PUBLIC:
                return AdapterInterface::VISIBILITY_PUBLIC;
            case FilesystemContract::VISIBILITY_PRIVATE:
                return AdapterInterface::VISIBILITY_PRIVATE;
        }

        throw new InvalidArgumentException("Unknown visibility: {$visibility}");
    }

    /**
     * Pass dynamic methods call onto Flysystem.
	 * 将动态方法调用传递给Flysystem
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, array $parameters)
    {
        return $this->driver->{$method}(...array_values($parameters));
    }
}
