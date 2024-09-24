<?php
/**
 * 基础，观察者生成命令
 */

namespace Illuminate\Foundation\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

class ObserverMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
	 * 控制台命令名
     *
     * @var string
     */
    protected $name = 'make:observer';

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Create a new observer class';

    /**
     * The type of class being generated.
	 * 生成类的类型
     *
     * @var string
     */
    protected $type = 'Observer';

    /**
     * Build the class with the given name.
	 * 构建类使用给定名
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        $stub = parent::buildClass($name);

        $model = $this->option('model');

        return $model ? $this->replaceModel($stub, $model) : $stub;
    }

    /**
     * Get the stub file for the generator.
	 * 得到生成器的存根文件
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->option('model')
                    ? __DIR__.'/stubs/observer.stub'
                    : __DIR__.'/stubs/observer.plain.stub';
    }

    /**
     * Replace the model for the given stub.
	 * 替换给定存根的模型
     *
     * @param  string  $stub
     * @param  string  $model
     * @return string
     */
    protected function replaceModel($stub, $model)
    {
        $model = str_replace('/', '\\', $model);

        $namespaceModel = $this->laravel->getNamespace().$model;

        if (Str::startsWith($model, '\\')) {
            $stub = str_replace('NamespacedDummyModel', trim($model, '\\'), $stub);
        } else {
            $stub = str_replace('NamespacedDummyModel', $namespaceModel, $stub);
        }

        $stub = str_replace(
            "use {$namespaceModel};\nuse {$namespaceModel};", "use {$namespaceModel};", $stub
        );

        $model = class_basename(trim($model, '\\'));

        $stub = str_replace('DocDummyModel', Str::snake($model, ' '), $stub);

        $stub = str_replace('DummyModel', $model, $stub);

        return str_replace('dummyModel', Str::camel($model), $stub);
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
        return $rootNamespace.'\Observers';
    }

    /**
     * Get the console command arguments.
	 * 得到控制台命令参数
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'The model that the observer applies to.'],
        ];
    }
}
