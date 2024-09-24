<?php
/**
 * 基础，测试生成命令
 */

namespace Illuminate\Foundation\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;

class TestMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
	 * 控制台命令名
     *
     * @var string
     */
    protected $signature = 'make:test {name : The name of the class} {--unit : Create a unit test}';

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Create a new test class';

    /**
     * The type of class being generated.
	 * 生成器类的类型
     *
     * @var string
     */
    protected $type = 'Test';

    /**
     * Get the stub file for the generator.
	 * 得到生成器的存根文件
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->option('unit')) {
            return __DIR__.'/stubs/unit-test.stub';
        }

        return __DIR__.'/stubs/test.stub';
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
        $name = Str::replaceFirst($this->rootNamespace(), '', $name);

        return base_path('tests').str_replace('\\', '/', $name).'.php';
    }

    /**
     * Get the default namespace for the class.
	 * 得到类的默认命名空间
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        if ($this->option('unit')) {
            return $rootNamespace.'\Unit';
        } else {
            return $rootNamespace.'\Feature';
        }
    }

    /**
     * Get the root namespace for the class.
	 * 得到类的根命名空间
     *
     * @return string
     */
    protected function rootNamespace()
    {
        return 'Tests';
    }
}
