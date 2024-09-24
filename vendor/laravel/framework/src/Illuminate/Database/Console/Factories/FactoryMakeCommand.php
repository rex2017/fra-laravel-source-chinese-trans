<?php
/**
 * 数据库，控制台工厂制造指令
 */

namespace Illuminate\Database\Console\Factories;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class FactoryMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
	 * 控制台命令名
     *
     * @var string
     */
    protected $name = 'make:factory';

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Create a new model factory';

    /**
     * The type of class being generated.
	 * 生成的类的类型
     *
     * @var string
     */
    protected $type = 'Factory';

    /**
     * Get the stub file for the generator.
	 * 得到生成器的存根文件
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/factory.stub';
    }

    /**
     * Build the class with the given name.
	 * 构建类使用给定的名称
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        $namespaceModel = $this->option('model')
                        ? $this->qualifyClass($this->option('model'))
                        : trim($this->rootNamespace(), '\\').'\\Model';

        $model = class_basename($namespaceModel);

        return str_replace(
            [
                'NamespacedDummyModel',
                'DummyModel',
            ],
            [
                $namespaceModel,
                $model,
            ],
            parent::buildClass($name)
        );
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
        $name = str_replace(
            ['\\', '/'], '', $this->argument('name')
        );

        return $this->laravel->databasePath()."/factories/{$name}.php";
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
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'The name of the model'],
        ];
    }
}
