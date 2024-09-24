<?php
/**
 * 路由，控制台中间件生成命令
 */

namespace Illuminate\Routing\Console;

use Illuminate\Console\GeneratorCommand;

class MiddlewareMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
	 * 控制台命令名称
     *
     * @var string
     */
    protected $name = 'make:middleware';

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Create a new middleware class';

    /**
     * The type of class being generated.
	 * 生成器类的类型
     *
     * @var string
     */
    protected $type = 'Middleware';

    /**
     * Get the stub file for the generator.
	 * 得到生成器存根文件
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/middleware.stub';
    }

    /**
     * Get the default namespace for the class.
	 * 得到默认命名空间
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Http\Middleware';
    }
}
