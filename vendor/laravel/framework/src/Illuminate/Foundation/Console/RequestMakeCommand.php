<?php
/**
 * 基础，请求生成命令
 */

namespace Illuminate\Foundation\Console;

use Illuminate\Console\GeneratorCommand;

class RequestMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
	 * 控制台命令名
     *
     * @var string
     */
    protected $name = 'make:request';

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Create a new form request class';

    /**
     * The type of class being generated.
	 * 生成类的类型
     *
     * @var string
     */
    protected $type = 'Request';

    /**
     * Get the stub file for the generator.
	 * 得到生成器的原始文件
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/request.stub';
    }

    /**
     * Get the default namespace for the class.
	 * 得到类的命名空间
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Http\Requests';
    }
}
