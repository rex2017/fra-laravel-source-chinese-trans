<?php
/**
 * 基础，作业生成命令
 */

namespace Illuminate\Foundation\Console;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class JobMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
	 * 控制台命令名
     *
     * @var string
     */
    protected $name = 'make:job';

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Create a new job class';

    /**
     * The type of class being generated.
	 * 被生成类的类型
     *
     * @var string
     */
    protected $type = 'Job';

    /**
     * Get the stub file for the generator.
	 * 得到存根文件为生成器
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->option('sync')
                        ? __DIR__.'/stubs/job.stub'
                        : __DIR__.'/stubs/job-queued.stub';
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
        return $rootNamespace.'\Jobs';
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
            ['sync', null, InputOption::VALUE_NONE, 'Indicates that job should be synchronous'],
        ];
    }
}
