<?php
/**
 * 基础，异常排除命令
 */

namespace Illuminate\Foundation\Console;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class ExceptionMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
	 * 控制台命令名
     *
     * @var string
     */
    protected $name = 'make:exception';

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Create a new custom exception class';

    /**
     * The type of class being generated.
	 * 类的类型被生成的
     *
     * @var string
     */
    protected $type = 'Exception';

    /**
     * Get the stub file for the generator.
	 * 得到生成器的存根文件
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->option('render')) {
            return $this->option('report')
                ? __DIR__.'/stubs/exception-render-report.stub'
                : __DIR__.'/stubs/exception-render.stub';
        }

        return $this->option('report')
            ? __DIR__.'/stubs/exception-report.stub'
            : __DIR__.'/stubs/exception.stub';
    }

    /**
     * Determine if the class already exists.
	 * 确定类是否存在
     *
     * @param  string  $rawName
     * @return bool
     */
    protected function alreadyExists($rawName)
    {
        return class_exists($this->rootNamespace().'Exceptions\\'.$rawName);
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
        return $rootNamespace.'\Exceptions';
    }

    /**
     * Get the console command options.
	 * 得到控制台命令选项
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['render', null, InputOption::VALUE_NONE, 'Create the exception with an empty render method'],

            ['report', null, InputOption::VALUE_NONE, 'Create the exception with an empty report method'],
        ];
    }
}
