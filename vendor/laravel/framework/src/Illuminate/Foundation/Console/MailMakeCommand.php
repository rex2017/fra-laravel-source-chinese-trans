<?php
/**
 * 基础，邮件生成命令
 */

namespace Illuminate\Foundation\Console;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class MailMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
	 * 控制台命令名
     *
     * @var string
     */
    protected $name = 'make:mail';

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Create a new email class';

    /**
     * The type of class being generated.
	 * 生成类的类型
     *
     * @var string
     */
    protected $type = 'Mail';

    /**
     * Execute the console command.
	 * 执行控制台命令
     *
     * @return void
     */
    public function handle()
    {
        if (parent::handle() === false && ! $this->option('force')) {
            return;
        }

        if ($this->option('markdown')) {
            $this->writeMarkdownTemplate();
        }
    }

    /**
     * Write the Markdown template for the mailable.
	 * 编写Markdown模板为邮件
     *
     * @return void
     */
    protected function writeMarkdownTemplate()
    {
        $path = resource_path('views/'.str_replace('.', '/', $this->option('markdown'))).'.blade.php';

        if (! $this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0755, true);
        }

        $this->files->put($path, file_get_contents(__DIR__.'/stubs/markdown.stub'));
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
        $class = parent::buildClass($name);

        if ($this->option('markdown')) {
            $class = str_replace('DummyView', $this->option('markdown'), $class);
        }

        return $class;
    }

    /**
     * Get the stub file for the generator.
	 * 得到生成器的存根文件
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->option('markdown')
                        ? __DIR__.'/stubs/markdown-mail.stub'
                        : __DIR__.'/stubs/mail.stub';
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
        return $rootNamespace.'\Mail';
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
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the mailable already exists'],

            ['markdown', 'm', InputOption::VALUE_OPTIONAL, 'Create a new Markdown template for the mailable'],
        ];
    }
}
