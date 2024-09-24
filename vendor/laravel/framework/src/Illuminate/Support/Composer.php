<?php
/**
 * 支持，Composer
 */

namespace Illuminate\Support;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class Composer
{
    /**
     * The filesystem instance.
	 * 文件系统实例
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The working path to regenerate from.
	 * 要重新生成的工作路径
     *
     * @var string|null
     */
    protected $workingPath;

    /**
     * Create a new Composer manager instance.
	 * 创建新的管理实例
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  string|null  $workingPath
     * @return void
     */
    public function __construct(Filesystem $files, $workingPath = null)
    {
        $this->files = $files;
        $this->workingPath = $workingPath;
    }

    /**
     * Regenerate the Composer autoloader files.
	 * 重新生成Composer自动加载器文件
     *
     * @param  string|array  $extra
     * @return void
     */
    public function dumpAutoloads($extra = '')
    {
        $extra = $extra ? (array) $extra : [];

        $command = array_merge($this->findComposer(), ['dump-autoload'], $extra);

        $this->getProcess($command)->run();
    }

    /**
     * Regenerate the optimized Composer autoloader files.
	 * 重新生成优化的Composer自动加载器文件
     *
     * @return void
     */
    public function dumpOptimized()
    {
        $this->dumpAutoloads('--optimize');
    }

    /**
     * Get the composer command for the environment.
	 * 得到环境的编写器命令
     *
     * @return array
     */
    protected function findComposer()
    {
        if ($this->files->exists($this->workingPath.'/composer.phar')) {
            return [$this->phpBinary(), 'composer.phar'];
        }

        return ['composer'];
    }

    /**
     * Get the PHP binary.
	 * 得到PHP二进制
     *
     * @return string
     */
    protected function phpBinary()
    {
        return ProcessUtils::escapeArgument((new PhpExecutableFinder)->find(false));
    }

    /**
     * Get a new Symfony process instance.
	 * 得到一个新的Symfony流程实例
     *
     * @param  array  $command
     * @return \Symfony\Component\Process\Process
     */
    protected function getProcess(array $command)
    {
        return (new Process($command, $this->workingPath))->setTimeout(null);
    }

    /**
     * Set the working path used by the class.
	 * 设置类使用的工作路径
     *
     * @param  string  $path
     * @return $this
     */
    public function setWorkingPath($path)
    {
        $this->workingPath = realpath($path);

        return $this;
    }
}
