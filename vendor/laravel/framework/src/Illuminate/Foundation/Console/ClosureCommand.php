<?php
/**
 * 基础，闭合命令
 */

namespace Illuminate\Foundation\Console;

use Closure;
use Illuminate\Console\Command;
use ReflectionFunction;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClosureCommand extends Command
{
    /**
     * The command callback.
	 * 命令回调
     *
     * @var \Closure
     */
    protected $callback;

    /**
     * Create a new command instance.
	 * 创建新的命令实例
     *
     * @param  string  $signature
     * @param  \Closure  $callback
     * @return void
     */
    public function __construct($signature, Closure $callback)
    {
        $this->callback = $callback;
        $this->signature = $signature;

        parent::__construct();
    }

    /**
     * Execute the console command.
	 * 执行控制台命令
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return mixed
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputs = array_merge($input->getArguments(), $input->getOptions());

        $parameters = [];

        foreach ((new ReflectionFunction($this->callback))->getParameters() as $parameter) {
            if (isset($inputs[$parameter->getName()])) {
                $parameters[$parameter->getName()] = $inputs[$parameter->getName()];
            }
        }

        return $this->laravel->call(
            $this->callback->bindTo($this, $this), $parameters
        );
    }

    /**
     * Set the description for the command.
	 * 设置命令描述
     *
     * @param  string  $description
     * @return $this
     */
    public function describe($description)
    {
        $this->setDescription($description);

        return $this;
    }
}
