<?php
/**
 * 基础，监听者生成命令
 */

namespace Illuminate\Foundation\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

class ListenerMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
	 * 控制台命令名
     *
     * @var string
     */
    protected $name = 'make:listener';

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Create a new event listener class';

    /**
     * The type of class being generated.
	 * 生成的类的类型
     *
     * @var string
     */
    protected $type = 'Listener';

    /**
     * Build the class with the given name.
	 * 构建类用给定的名称
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        $event = $this->option('event');

        if (! Str::startsWith($event, [
            $this->laravel->getNamespace(),
            'Illuminate',
            '\\',
        ])) {
            $event = $this->laravel->getNamespace().'Events\\'.$event;
        }

        $stub = str_replace(
            'DummyEvent', class_basename($event), parent::buildClass($name)
        );

        return str_replace(
            'DummyFullEvent', trim($event, '\\'), $stub
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
        if ($this->option('queued')) {
            return $this->option('event')
                        ? __DIR__.'/stubs/listener-queued.stub'
                        : __DIR__.'/stubs/listener-queued-duck.stub';
        }

        return $this->option('event')
                    ? __DIR__.'/stubs/listener.stub'
                    : __DIR__.'/stubs/listener-duck.stub';
    }

    /**
     * Determine if the class already exists.
	 * 确定类是否已经存在
     *
     * @param  string  $rawName
     * @return bool
     */
    protected function alreadyExists($rawName)
    {
        return class_exists($rawName);
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
        return $rootNamespace.'\Listeners';
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
            ['event', 'e', InputOption::VALUE_OPTIONAL, 'The event class being listened for'],

            ['queued', null, InputOption::VALUE_NONE, 'Indicates the event listener should be queued'],
        ];
    }
}
