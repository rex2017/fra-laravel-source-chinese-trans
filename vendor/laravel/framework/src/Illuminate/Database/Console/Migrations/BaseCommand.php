<?php
/**
 * 数据库，迁移基本命令
 */

namespace Illuminate\Database\Console\Migrations;

use Illuminate\Console\Command;

class BaseCommand extends Command
{
    /**
     * Get all of the migration paths.
	 * 得到所有迁移路径
     *
     * @return array
     */
    protected function getMigrationPaths()
    {
        // Here, we will check to see if a path option has been defined. If it has we will
        // use the path relative to the root of the installation folder so our database
        // migrations may be run for any customized path from within the application.
		// 在这里，我们将检查是否定义了路径选项。如果有，我们将使用相对于安装文件夹根的路径，
		// 以便我们的数据库可以从应用程序内对任何自定义路径运行迁移。
        if ($this->input->hasOption('path') && $this->option('path')) {
            return collect($this->option('path'))->map(function ($path) {
                return ! $this->usingRealPath()
                                ? $this->laravel->basePath().'/'.$path
                                : $path;
            })->all();
        }

        return array_merge(
            $this->migrator->paths(), [$this->getMigrationPath()]
        );
    }

    /**
     * Determine if the given path(s) are pre-resolved "real" paths.
	 * 确定给定的路径是否是预先解析的真实路径
     *
     * @return bool
     */
    protected function usingRealPath()
    {
        return $this->input->hasOption('realpath') && $this->option('realpath');
    }

    /**
     * Get the path to the migration directory.
	 * 得到迁移目录的路径
     *
     * @return string
     */
    protected function getMigrationPath()
    {
        return $this->laravel->databasePath().DIRECTORY_SEPARATOR.'migrations';
    }
}
