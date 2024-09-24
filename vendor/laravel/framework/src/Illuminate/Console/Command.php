<?php
/**
 * 控制台命令
 */

namespace Illuminate\Console;

use Illuminate\Support\Traits\Macroable;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Command extends SymfonyCommand
{
    use Concerns\CallsCommands,
        Concerns\HasParameters,
        Concerns\InteractsWithIO,
        Macroable;

    /**
     * The Laravel application instance.
	 * laravel应用实例
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $laravel;

    /**
     * The name and signature of the console command.
	 * 名称和签名控制台命令
     *
     * @var string
     */
    protected $signature;

    /**
     * The console command name.
	 * 控制台命令名
     *
     * @var string
     */
    protected $name;

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string|null
     */
    protected $description;

    /**
     * The console command help text.
	 * 控制台命令帮助文本
     *
     * @var string|null
     */
    protected $help;

    /**
     * Indicates whether the command should be shown in the Artisan command list.
	 * 指明该命令是否应该显示在Artisan命令列表中
     *
     * @var bool
     */
    protected $hidden = false;

    /**
     * Create a new console command instance.
	 * 创建新的控制台命令实例
     *
     * @return void
     */
    public function __construct()
    {
        // We will go ahead and set the name, description, and parameters on console
        // commands just to make things a little easier on the developer. This is
        // so they don't have to all be manually specified in the constructors.
		// 我们将继续在控制台命令上设置名称、描述和参数，只是为了让开发人员更容易一些。
		// 这样它们就不必在构造函数中手动指定。
        if (isset($this->signature)) {
            $this->configureUsingFluentDefinition();
        } else {
            parent::__construct($this->name);
        }

        // Once we have constructed the command, we'll set the description and other
        // related properties of the command. If a signature wasn't used to build
        // the command we'll set the arguments and the options on this command.
		// 构造完命令后，我们将设置命令的描述和其他相关属性。
		// 如果没有使用签名来构建命令，我们将设置此命令的参数和选项。
        $this->setDescription((string) $this->description);

        $this->setHelp((string) $this->help);

        $this->setHidden($this->isHidden());

        if (! isset($this->signature)) {
            $this->specifyParameters();
        }
    }

    /**
     * Configure the console command using a fluent definition.
	 * 配置console命令使用连贯的定义
     *
     * @return void
     */
    protected function configureUsingFluentDefinition()
    {
        [$name, $arguments, $options] = Parser::parse($this->signature);

        parent::__construct($this->name = $name);

        // After parsing the signature we will spin through the arguments and options
        // and set them on this command. These will already be changed into proper
        // instances of these "InputArgument" and "InputOption" Symfony classes.
		// 解析签名后，我们将遍历参数和选项，并在此命令上设置它们。
		// 这些已经被更改为这些“InputArgument”和“InputOption”Symfony类的适当实例。
        $this->getDefinition()->addArguments($arguments);
        $this->getDefinition()->addOptions($options);
    }

    /**
     * Run the console command.
	 * 运行控制台命令
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return int
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        $this->output = $this->laravel->make(
            OutputStyle::class, ['input' => $input, 'output' => $output]
        );

        return parent::run(
            $this->input = $input, $this->output
        );
    }

    /**
     * Execute the console command.
	 * 运行控制台命令
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return mixed
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        return $this->laravel->call([$this, 'handle']);
    }

    /**
     * Resolve the console command instance for the given command.
	 * 解析给定命令的控制台命令实例
     *
     * @param  \Symfony\Component\Console\Command\Command|string  $command
     * @return \Symfony\Component\Console\Command\Command
     */
    protected function resolveCommand($command)
    {
        if (! class_exists($command)) {
            return $this->getApplication()->find($command);
        }

        $command = $this->laravel->make($command);

        if ($command instanceof SymfonyCommand) {
            $command->setApplication($this->getApplication());
        }

        if ($command instanceof self) {
            $command->setLaravel($this->getLaravel());
        }

        return $command;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return $this->hidden;
    }

    /**
     * {@inheritdoc}
     */
    public function setHidden($hidden)
    {
        parent::setHidden($this->hidden = $hidden);

        return $this;
    }

    /**
     * Get the Laravel application instance.
	 * 得到Laravel应用实例
     *
     * @return \Illuminate\Contracts\Foundation\Application
     */
    public function getLaravel()
    {
        return $this->laravel;
    }

    /**
     * Set the Laravel application instance.
	 * 设置Laravel应用实例
     *
     * @param  \Illuminate\Contracts\Container\Container  $laravel
     * @return void
     */
    public function setLaravel($laravel)
    {
        $this->laravel = $laravel;
    }
}
