<?php
/**
 * 基础，通道创建命令
 */

namespace Illuminate\Foundation\Console;

use Illuminate\Console\GeneratorCommand;

class ChannelMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
	 * 控制台命令名
     *
     * @var string
     */
    protected $name = 'make:channel';

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Create a new channel class';

    /**
     * The type of class being generated.
	 * 生成的类的类型
     *
     * @var string
     */
    protected $type = 'Channel';

    /**
     * Build the class with the given name.
	 * 构建类用给定的名称
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        return str_replace(
            'DummyUser',
            class_basename($this->userProviderModel()),
            parent::buildClass($name)
        );
    }

    /**
     * Get the stub file for the generator.
	 * 得到生成器的存根文件
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/channel.stub';
    }

    /**
     * Get the default namespace for the class.
	 * 得到类的默认名称空间
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Broadcasting';
    }
}
