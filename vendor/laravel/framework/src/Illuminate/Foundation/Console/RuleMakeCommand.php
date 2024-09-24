<?php
/**
 * 基础，规则生成命令
 */

namespace Illuminate\Foundation\Console;

use Illuminate\Console\GeneratorCommand;

class RuleMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
	 * 控制台命令名
     *
     * @var string
     */
    protected $name = 'make:rule';

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Create a new validation rule';

    /**
     * The type of class being generated.
	 * 生成器类的类型
     *
     * @var string
     */
    protected $type = 'Rule';

    /**
     * Get the stub file for the generator.
	 * 得到生成器的存根文件
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/rule.stub';
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
        return $rootNamespace.'\Rules';
    }
}
