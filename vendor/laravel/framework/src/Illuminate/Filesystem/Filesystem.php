<?php
/**
 * 文件系统
 */

namespace Illuminate\Filesystem;

use ErrorException;
use FilesystemIterator;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Traits\Macroable;
use Symfony\Component\Finder\Finder;

class Filesystem
{
    use Macroable;

    /**
     * Determine if a file or directory exists.
	 * 确定是否文件或目录存在
     *
     * @param  string  $path
     * @return bool
     */
    public function exists($path)
    {
        return file_exists($path);
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
     * Get the contents of a file.
	 * 得到文件内容
     *
     * @param  string  $path
     * @param  bool  $lock
     * @return string
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function get($path, $lock = false)
    {
        if ($this->isFile($path)) {
            return $lock ? $this->sharedGet($path) : file_get_contents($path);
        }

        throw new FileNotFoundException("File does not exist at path {$path}");
    }

    /**
     * Get contents of a file with shared access.
	 * 得到有共享权限文件内容
     *
     * @param  string  $path
     * @return string
     */
    public function sharedGet($path)
    {
        $contents = '';

        $handle = fopen($path, 'rb');

        if ($handle) {
            try {
                if (flock($handle, LOCK_SH)) {
                    clearstatcache(true, $path);

                    $contents = fread($handle, $this->size($path) ?: 1);

                    flock($handle, LOCK_UN);
                }
            } finally {
                fclose($handle);
            }
        }

        return $contents;
    }

    /**
     * Get the returned value of a file.
	 * 得到文件返回值
     *
     * @param  string  $path
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function getRequire($path)
    {
        if ($this->isFile($path)) {
            return require $path;
        }

        throw new FileNotFoundException("File does not exist at path {$path}");
    }

    /**
     * Require the given file once.
	 * 引入文件
     *
     * @param  string  $file
     * @return mixed
     */
    public function requireOnce($file)
    {
        require_once $file;
    }

    /**
     * Get the MD5 hash of the file at the given path.
	 * 得到文件MD5哈希
     *
     * @param  string  $path
     * @return string
     */
    public function hash($path)
    {
        return md5_file($path);
    }

    /**
     * Write the contents of a file.
	 * 写入文件内容
     *
     * @param  string  $path
     * @param  string  $contents
     * @param  bool  $lock
     * @return int|bool
     */
    public function put($path, $contents, $lock = false)
    {
        return file_put_contents($path, $contents, $lock ? LOCK_EX : 0);
    }

    /**
     * Write the contents of a file, replacing it atomically if it already exists.
	 * 写入文件的内容，如果它已经存在，则自动替换它
     *
     * @param  string  $path
     * @param  string  $content
     * @return void
     */
    public function replace($path, $content)
    {
        // If the path already exists and is a symlink, get the real path...
		// 如果路径已经存在并且是一个符号链接，则获取真实路径…
        clearstatcache(true, $path);

        $path = realpath($path) ?: $path;

        $tempPath = tempnam(dirname($path), basename($path));

        // Fix permissions of tempPath because `tempnam()` creates it with permissions set to 0600...
		// 修复临时路径，因为'tempnam()'创建它时权限设置为0600…
        chmod($tempPath, 0777 - umask());

        file_put_contents($tempPath, $content);

        rename($tempPath, $path);
    }

    /**
     * Prepend to a file.
	 * 添加至文件
     *
     * @param  string  $path
     * @param  string  $data
     * @return int
     */
    public function prepend($path, $data)
    {
        if ($this->exists($path)) {
            return $this->put($path, $data.$this->get($path));
        }

        return $this->put($path, $data);
    }

    /**
     * Append to a file.
	 * 追加文件
     *
     * @param  string  $path
     * @param  string  $data
     * @return int
     */
    public function append($path, $data)
    {
        return file_put_contents($path, $data, FILE_APPEND);
    }

    /**
     * Get or set UNIX mode of a file or directory.
	 * 得到或设置文件或目录的UNIX模式
     *
     * @param  string  $path
     * @param  int|null  $mode
     * @return mixed
     */
    public function chmod($path, $mode = null)
    {
        if ($mode) {
            return chmod($path, $mode);
        }

        return substr(sprintf('%o', fileperms($path)), -4);
    }

    /**
     * Delete the file at a given path.
	 * 删除文件
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
                if (! @unlink($path)) {
                    $success = false;
                }
            } catch (ErrorException $e) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Move a file to a new location.
	 * 移动文件到新位置
     *
     * @param  string  $path
     * @param  string  $target
     * @return bool
     */
    public function move($path, $target)
    {
        return rename($path, $target);
    }

    /**
     * Copy a file to a new location.
	 * 复制文件到新位置
     *
     * @param  string  $path
     * @param  string  $target
     * @return bool
     */
    public function copy($path, $target)
    {
        return copy($path, $target);
    }

    /**
     * Create a symlink to the target file or directory. On Windows, a hard link is created if the target is a file.
	 * 创建指向目标文件或目录的符号链接。在Windows操作系统中，如果目标是文件，则创建硬链接。
     *
     * @param  string  $target
     * @param  string  $link
     * @return void
     */
    public function link($target, $link)
    {
        if (! windows_os()) {
            return symlink($target, $link);
        }

        $mode = $this->isDirectory($target) ? 'J' : 'H';

        exec("mklink /{$mode} ".escapeshellarg($link).' '.escapeshellarg($target));
    }

    /**
     * Extract the file name from a file path.
	 * 提取文件名从文件路径中
     *
     * @param  string  $path
     * @return string
     */
    public function name($path)
    {
        return pathinfo($path, PATHINFO_FILENAME);
    }

    /**
     * Extract the trailing name component from a file path.
	 * 提取尾随名称组件
     *
     * @param  string  $path
     * @return string
     */
    public function basename($path)
    {
        return pathinfo($path, PATHINFO_BASENAME);
    }

    /**
     * Extract the parent directory from a file path.
	 * 提取父目录从文件路径
     *
     * @param  string  $path
     * @return string
     */
    public function dirname($path)
    {
        return pathinfo($path, PATHINFO_DIRNAME);
    }

    /**
     * Extract the file extension from a file path.
	 * 提取文件扩展名从文件路径
     *
     * @param  string  $path
     * @return string
     */
    public function extension($path)
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * Get the file type of a given file.
	 * 得到文件类型
     *
     * @param  string  $path
     * @return string
     */
    public function type($path)
    {
        return filetype($path);
    }

    /**
     * Get the mime-type of a given file.
	 * 得到给定文件的mime类型
     *
     * @param  string  $path
     * @return string|false
     */
    public function mimeType($path)
    {
        return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
    }

    /**
     * Get the file size of a given file.
	 & 得到文件大小
     *
     * @param  string  $path
     * @return int
     */
    public function size($path)
    {
        return filesize($path);
    }

    /**
     * Get the file's last modification time.
	 * 得到文档最后修改时间
     *
     * @param  string  $path
     * @return int
     */
    public function lastModified($path)
    {
        return filemtime($path);
    }

    /**
     * Determine if the given path is a directory.
	 * 确定是否给定路径是目录
     *
     * @param  string  $directory
     * @return bool
     */
    public function isDirectory($directory)
    {
        return is_dir($directory);
    }

    /**
     * Determine if the given path is readable.
	 * 确定是否给定路径可读
     *
     * @param  string  $path
     * @return bool
     */
    public function isReadable($path)
    {
        return is_readable($path);
    }

    /**
     * Determine if the given path is writable.
	 * 确定是否给定路径可写
     *
     * @param  string  $path
     * @return bool
     */
    public function isWritable($path)
    {
        return is_writable($path);
    }

    /**
     * Determine if the given path is a file.
	 * 确定是否给定路径为文件
     *
     * @param  string  $file
     * @return bool
     */
    public function isFile($file)
    {
        return is_file($file);
    }

    /**
     * Find path names matching a given pattern.
	 * 查找与给定模式匹配的路径名
     *
     * @param  string  $pattern
     * @param  int  $flags
     * @return array
     */
    public function glob($pattern, $flags = 0)
    {
        return glob($pattern, $flags);
    }

    /**
     * Get an array of all files in a directory.
	 * 得到目录下文件数组
     *
     * @param  string  $directory
     * @param  bool  $hidden
     * @return \Symfony\Component\Finder\SplFileInfo[]
     */
    public function files($directory, $hidden = false)
    {
        return iterator_to_array(
            Finder::create()->files()->ignoreDotFiles(! $hidden)->in($directory)->depth(0)->sortByName(),
            false
        );
    }

    /**
     * Get all of the files from the given directory (recursive).
	 * 得到目录下文件数组(递归)
     *
     * @param  string  $directory
     * @param  bool  $hidden
     * @return \Symfony\Component\Finder\SplFileInfo[]
     */
    public function allFiles($directory, $hidden = false)
    {
        return iterator_to_array(
            Finder::create()->files()->ignoreDotFiles(! $hidden)->in($directory)->sortByName(),
            false
        );
    }

    /**
     * Get all of the directories within a given directory.
	 * 得到目录下所有目录
     *
     * @param  string  $directory
     * @return array
     */
    public function directories($directory)
    {
        $directories = [];

        foreach (Finder::create()->in($directory)->directories()->depth(0)->sortByName() as $dir) {
            $directories[] = $dir->getPathname();
        }

        return $directories;
    }

    /**
     * Ensure a directory exists.
	 * 确保目录存在
     *
     * @param  string  $path
     * @param  int  $mode
     * @param  bool  $recursive
     * @return void
     */
    public function ensureDirectoryExists($path, $mode = 0755, $recursive = true)
    {
        if (! $this->isDirectory($path)) {
            $this->makeDirectory($path, $mode, $recursive);
        }
    }

    /**
     * Create a directory.
	 * 创建目录
     *
     * @param  string  $path
     * @param  int  $mode
     * @param  bool  $recursive
     * @param  bool  $force
     * @return bool
     */
    public function makeDirectory($path, $mode = 0755, $recursive = false, $force = false)
    {
        if ($force) {
            return @mkdir($path, $mode, $recursive);
        }

        return mkdir($path, $mode, $recursive);
    }

    /**
     * Move a directory.
	 * 移动目录
     *
     * @param  string  $from
     * @param  string  $to
     * @param  bool  $overwrite
     * @return bool
     */
    public function moveDirectory($from, $to, $overwrite = false)
    {
        if ($overwrite && $this->isDirectory($to) && ! $this->deleteDirectory($to)) {
            return false;
        }

        return @rename($from, $to) === true;
    }

    /**
     * Copy a directory from one location to another.
	 * 复制文件至另外
     *
     * @param  string  $directory
     * @param  string  $destination
     * @param  int|null  $options
     * @return bool
     */
    public function copyDirectory($directory, $destination, $options = null)
    {
        if (! $this->isDirectory($directory)) {
            return false;
        }

        $options = $options ?: FilesystemIterator::SKIP_DOTS;

        // If the destination directory does not actually exist, we will go ahead and
        // create it recursively, which just gets the destination prepared to copy
        // the files over. Once we make the directory we'll proceed the copying.
		// 如果目标目录实际上不存在，我们将继续递归创建它，这会将目标做好复制的准备。
		// 一旦我们创建了目录，我们将继续复制。
        if (! $this->isDirectory($destination)) {
            $this->makeDirectory($destination, 0777, true);
        }

        $items = new FilesystemIterator($directory, $options);

        foreach ($items as $item) {
            // As we spin through items, we will check to see if the current file is actually
            // a directory or a file. When it is actually a directory we will need to call
            // back into this function recursively to keep copying these nested folders.
			// 当我们浏览项目时，我们将检查当前文件是否真的是目录或文件。
			// 当它是一个目录时，我们需要调用递归返回此函数以继续复制这些嵌套文件夹。
            $target = $destination.'/'.$item->getBasename();

            if ($item->isDir()) {
                $path = $item->getPathname();

                if (! $this->copyDirectory($path, $target, $options)) {
                    return false;
                }
            }

            // If the current items is just a regular file, we will just copy this to the new
            // location and keep looping. If for some reason the copy fails we'll bail out
            // and return false, so the developer is aware that the copy process failed.
			// 如果当前项目人是一个常规文件，我们将只需将其复制到新的定位并保持循环。
			// 如果出于某种原因，副本失败了，我们将退出并返回false，这样开发人员就知道了。
            else {
                if (! $this->copy($item->getPathname(), $target)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Recursively delete a directory.
	 * 递归删除目录
     *
     * The directory itself may be optionally preserved.
     *
     * @param  string  $directory
     * @param  bool  $preserve
     * @return bool
     */
    public function deleteDirectory($directory, $preserve = false)
    {
        if (! $this->isDirectory($directory)) {
            return false;
        }

        $items = new FilesystemIterator($directory);

        foreach ($items as $item) {
            // If the item is a directory, we can just recurse into the function and
            // delete that sub-directory otherwise we'll just delete the file and
            // keep iterating through each file until the directory is cleaned.
			// 如果项目是一个目录，我们可以递归到函数中删除该子目录，
			// 否则我们只能删除文件并不断迭代每个文件，直到目录被清理干净。
            if ($item->isDir() && ! $item->isLink()) {
                $this->deleteDirectory($item->getPathname());
            }

            // If the item is just a file, we can go ahead and delete it since we're
            // just looping through and waxing all of the files in this directory
            // and calling directories recursively, so we delete the real path.
			// 如果该项目只是一个文件，我们可以继续删除它。
			// 因为我们只需循环并对该目录中的所有文件递归调用，以至删除真实路径。
            else {
                $this->delete($item->getPathname());
            }
        }

        if (! $preserve) {
            @rmdir($directory);
        }

        return true;
    }

    /**
     * Remove all of the directories within a given directory.
	 * 删除多个目录
     *
     * @param  string  $directory
     * @return bool
     */
    public function deleteDirectories($directory)
    {
        $allDirectories = $this->directories($directory);

        if (! empty($allDirectories)) {
            foreach ($allDirectories as $directoryName) {
                $this->deleteDirectory($directoryName);
            }

            return true;
        }

        return false;
    }

    /**
     * Empty the specified directory of all files and folders.
	 * 清空指定目录下的所有文件和文件夹
     *
     * @param  string  $directory
     * @return bool
     */
    public function cleanDirectory($directory)
    {
        return $this->deleteDirectory($directory, true);
    }
}
