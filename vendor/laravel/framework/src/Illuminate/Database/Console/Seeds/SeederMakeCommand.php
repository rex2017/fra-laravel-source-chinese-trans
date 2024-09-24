<?php
/**
 * 数据库，种子制作命令
 */

namespace Illuminate\Database\Console\Seeds;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;

class SeederMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
	 * 控制台命令名
     *
     * @var string
     */
    protected $name = 'make:seeder';

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Create a new seeder class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Seeder';

    /**
     * The Composer instance.
	 * composer实例
     *
     * @var \Illuminate\Support\Composer
     */
    protected $composer;

    /**
     * Create a new command instance.
	 * 创建新的命令实例
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  \Illuminate\Support\Composer  $composer
     * @return void
     */
    public function __construct(Filesystem $files, Composer $composer)
    {
        parent::__construct($files);

        $this->composer = $composer;
    }

    /**
     * Execute the console command.
	 * 执行控制台实例
     *
     * @return void
     */
    public function handle()
    {
        parent::handle();

        $this->composer->dumpAutoloads();
    }

    /**
     * Get the stub file for the generator.
	 * 得到生成器的存根文件
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/seeder.stub';
    }

    /**
     * Get the destination class path.
	 * 得到目标类路径
     *
     * @param  string  $name
     * @return string
     */
    protected function getPath($name)
    {
        return $this->laravel->databasePath().'/seeds/'.$name.'.php';
    }

    /**
     * Parse the class name and format according to the root namespace.
	 * 解析类名和格式根据根命名空间
     *
     * @param  string  $name
     * @return string
     */
    protected function qualifyClass($name)
    {
        return $name;
    }
}
