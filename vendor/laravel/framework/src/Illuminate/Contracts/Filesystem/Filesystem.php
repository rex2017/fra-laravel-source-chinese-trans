<?php
/**
 * 契约，文件系统接口
 */

namespace Illuminate\Contracts\Filesystem;

interface Filesystem
{
    /**
     * The public visibility setting.
	 * 公共可见设置
     *
     * @var string
     */
    const VISIBILITY_PUBLIC = 'public';

    /**
     * The private visibility setting.
	 * 私有可见设置
     *
     * @var string
     */
    const VISIBILITY_PRIVATE = 'private';

    /**
     * Determine if a file exists.
	 * 确定文件是否存在
     *
     * @param  string  $path
     * @return bool
     */
    public function exists($path);

    /**
     * Get the contents of a file.
	 * 得到文件内容
     *
     * @param  string  $path
     * @return string
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function get($path);

    /**
     * Get a resource to read the file.
	 * 得到读取文件的资源
     *
     * @param  string  $path
     * @return resource|null The path resource or null on failure.
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function readStream($path);

    /**
     * Write the contents of a file.
	 * 写入文件的内容
     *
     * @param  string  $path
     * @param  string|resource  $contents
     * @param  mixed  $options
     * @return bool
     */
    public function put($path, $contents, $options = []);

    /**
     * Write a new file using a stream.
	 * 写一个新文件使用流
     *
     * @param  string  $path
     * @param  resource  $resource
     * @param  array  $options
     * @return bool
     *
     * @throws \InvalidArgumentException If $resource is not a file handle.
     * @throws \Illuminate\Contracts\Filesystem\FileExistsException
     */
    public function writeStream($path, $resource, array $options = []);

    /**
     * Get the visibility for the given path.
	 * 得到给定路径是否可用
     *
     * @param  string  $path
     * @return string
     */
    public function getVisibility($path);

    /**
     * Set the visibility for the given path.
	 * 设置给定路径是否可用
     *
     * @param  string  $path
     * @param  string  $visibility
     * @return bool
     */
    public function setVisibility($path, $visibility);

    /**
     * Prepend to a file.
	 * 添加至文件
     *
     * @param  string  $path
     * @param  string  $data
     * @return bool
     */
    public function prepend($path, $data);

    /**
     * Append to a file.
	 * 追加一个文件
     *
     * @param  string  $path
     * @param  string  $data
     * @return bool
     */
    public function append($path, $data);

    /**
     * Delete the file at a given path.
	 * 删除文档路径
     *
     * @param  string|array  $paths
     * @return bool
     */
    public function delete($paths);

    /**
     * Copy a file to a new location.
	 * 复制文件到新地方
     *
     * @param  string  $from
     * @param  string  $to
     * @return bool
     */
    public function copy($from, $to);

    /**
     * Move a file to a new location.
	 * 移动一个文件到新地方
     *
     * @param  string  $from
     * @param  string  $to
     * @return bool
     */
    public function move($from, $to);

    /**
     * Get the file size of a given file.
	 * 得到文件大小
     *
     * @param  string  $path
     * @return int
     */
    public function size($path);

    /**
     * Get the file's last modification time.
	 * 得到文件最后修改时间
     *
     * @param  string  $path
     * @return int
     */
    public function lastModified($path);

    /**
     * Get an array of all files in a directory.
	 * 得到目录下所有文件清单
     *
     * @param  string|null  $directory
     * @param  bool  $recursive
     * @return array
     */
    public function files($directory = null, $recursive = false);

    /**
     * Get all of the files from the given directory (recursive).
	 * 得到所有的文件从指定目录(资源)
     *
     * @param  string|null  $directory
     * @return array
     */
    public function allFiles($directory = null);

    /**
     * Get all of the directories within a given directory.
	 * 得到所有的目录从指定目录
     *
     * @param  string|null  $directory
     * @param  bool  $recursive
     * @return array
     */
    public function directories($directory = null, $recursive = false);

    /**
     * Get all (recursive) of the directories within a given directory.
	 * 得到给定目录中的所有(递归)目录
     *
     * @param  string|null  $directory
     * @return array
     */
    public function allDirectories($directory = null);

    /**
     * Create a directory.
	 * 创建目录
     *
     * @param  string  $path
     * @return bool
     */
    public function makeDirectory($path);

    /**
     * Recursively delete a directory.
	 * 递归删除目录
     *
     * @param  string  $directory
     * @return bool
     */
    public function deleteDirectory($directory);
}
