<?php
/**
 * 基础，政策生成命令
 */

namespace Illuminate\Foundation\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

class PolicyMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
	 * 控制台命令名
     *
     * @var string
     */
    protected $name = 'make:policy';

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Create a new policy class';

    /**
     * The type of class being generated.
	 * 生成类的类型
     *
     * @var string
     */
    protected $type = 'Policy';

    /**
     * Build the class with the given name.
	 * 构建类使用给定名称
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        $stub = $this->replaceUserNamespace(
            parent::buildClass($name)
        );

        $model = $this->option('model');

        return $model ? $this->replaceModel($stub, $model) : $stub;
    }

    /**
     * Replace the User model namespace.
	 * 替换User模型命名空间
     *
     * @param  string  $stub
     * @return string
     */
    protected function replaceUserNamespace($stub)
    {
        $model = $this->userProviderModel();

        if (! $model) {
            return $stub;
        }

        return str_replace(
            $this->rootNamespace().'User',
            $model,
            $stub
        );
    }

    /**
     * Replace the model for the given stub.
	 * 替模型为给定存根
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

        $dummyUser = class_basename($this->userProviderModel());

        $dummyModel = Str::camel($model) === 'user' ? 'model' : $model;

        $stub = str_replace('DocDummyModel', Str::snake($dummyModel, ' '), $stub);

        $stub = str_replace('DummyModel', $model, $stub);

        $stub = str_replace('dummyModel', Str::camel($dummyModel), $stub);

        $stub = str_replace('DummyUser', $dummyUser, $stub);

        return str_replace('DocDummyPluralModel', Str::snake(Str::pluralStudly($dummyModel), ' '), $stub);
    }

    /**
     * Get the stub file for the generator.
	 * 得到存根文件为生成器
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->option('model')
                    ? __DIR__.'/stubs/policy.stub'
                    : __DIR__.'/stubs/policy.plain.stub';
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
        return $rootNamespace.'\Policies';
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
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'The model that the policy applies to'],
        ];
    }
}
