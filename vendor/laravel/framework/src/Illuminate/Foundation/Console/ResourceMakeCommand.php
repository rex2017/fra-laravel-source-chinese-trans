<?php
/**
 * 基础，资源生成命令
 */

namespace Illuminate\Foundation\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

class ResourceMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
	 * 控制台命令名
     *
     * @var string
     */
    protected $name = 'make:resource';

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Create a new resource';

    /**
     * The type of class being generated.
	 * 生成类的类型
     *
     * @var string
     */
    protected $type = 'Resource';

    /**
     * Execute the console command.
	 * 执行控制台命令
     *
     * @return void
     */
    public function handle()
    {
        if ($this->collection()) {
            $this->type = 'Resource collection';
        }

        parent::handle();
    }

    /**
     * Get the stub file for the generator.
	 * 得到生成器的存根文件
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->collection()
                    ? __DIR__.'/stubs/resource-collection.stub'
                    : __DIR__.'/stubs/resource.stub';
    }

    /**
     * Determine if the command is generating a resource collection.
	 * 确定该命令是否正在生成资源集合
     *
     * @return bool
     */
    protected function collection()
    {
        return $this->option('collection') ||
               Str::endsWith($this->argument('name'), 'Collection');
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
        return $rootNamespace.'\Http\Resources';
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
            ['collection', 'c', InputOption::VALUE_NONE, 'Create a resource collection'],
        ];
    }
}
