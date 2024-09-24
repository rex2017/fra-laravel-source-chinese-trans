<?php
/**
 * 基础，事件生成命令
 */

namespace Illuminate\Foundation\Console;

use Illuminate\Console\GeneratorCommand;

class EventMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
	 * 控制台命令名
     *
     * @var string
     */
    protected $name = 'make:event';

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Create a new event class';

    /**
     * The type of class being generated.
	 * 生成的类的类型
     *
     * @var string
     */
    protected $type = 'Event';

    /**
     * Determine if the class already exists.
	 * 确定类是否已存在
     *
     * @param  string  $rawName
     * @return bool
     */
    protected function alreadyExists($rawName)
    {
        return class_exists($rawName);
    }

    /**
     * Get the stub file for the generator.
	 * 得到存根文件为生成器
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/event.stub';
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
        return $rootNamespace.'\Events';
    }
}
