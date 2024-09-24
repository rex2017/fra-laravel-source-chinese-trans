<?php
/**
 * 文件Session处理
 */

namespace Illuminate\Session;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Carbon;
use SessionHandlerInterface;
use Symfony\Component\Finder\Finder;

class FileSessionHandler implements SessionHandlerInterface
{
    /**
     * The filesystem instance.
	 * 文件系统实例
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The path where sessions should be stored.
	 * 应该存储会话的路径
     *
     * @var string
     */
    protected $path;

    /**
     * The number of minutes the session should be valid.
	 * 会话有效的分钟数
     *
     * @var int
     */
    protected $minutes;

    /**
     * Create a new file driven handler instance.
	 * 创建新的文件驱动处理程序实例
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  string  $path
     * @param  int  $minutes
     * @return void
     */
    public function __construct(Filesystem $files, $path, $minutes)
    {
        $this->path = $path;
        $this->files = $files;
        $this->minutes = $minutes;
    }

    /**
     * {@inheritdoc}
     */
    public function open($savePath, $sessionName)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read($sessionId)
    {
        if ($this->files->isFile($path = $this->path.'/'.$sessionId)) {
            if ($this->files->lastModified($path) >= Carbon::now()->subMinutes($this->minutes)->getTimestamp()) {
                return $this->files->sharedGet($path);
            }
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function write($sessionId, $data)
    {
        $this->files->put($this->path.'/'.$sessionId, $data, true);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($sessionId)
    {
        $this->files->delete($this->path.'/'.$sessionId);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function gc($lifetime)
    {
        $files = Finder::create()
                    ->in($this->path)
                    ->files()
                    ->ignoreDotFiles(true)
                    ->date('<= now - '.$lifetime.' seconds');

        foreach ($files as $file) {
            $this->files->delete($file->getRealPath());
        }
    }
}
