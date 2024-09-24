<?php
/**
 * 控制台，有参数
 */

namespace Illuminate\Console\Concerns;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

trait HasParameters
{
    /**
     * Specify the arguments and options on the command.
	 * 指定命令上的参数和选项
     *
     * @return void
     */
    protected function specifyParameters()
    {
        // We will loop through all of the arguments and options for the command and
        // set them all on the base command instance. This specifies what can get
        // passed into these commands as "parameters" to control the execution.
		// 我们将遍历命令的所有参数和选项并设置他们全部在基本命令实例上。
		// 这指定了可以获得的内容作为"参数"传递给这些命令以控制执行。
        foreach ($this->getArguments() as $arguments) {
            if ($arguments instanceof InputArgument) {
                $this->getDefinition()->addArgument($arguments);
            } else {
                $this->addArgument(...array_values($arguments));
            }
        }

        foreach ($this->getOptions() as $options) {
            if ($options instanceof InputOption) {
                $this->getDefinition()->addOption($options);
            } else {
                $this->addOption(...array_values($options));
            }
        }
    }

    /**
     * Get the console command arguments.
	 * 得到控制台命令参数
     *
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }

    /**
     * Get the console command options.
	 * 得到控制台命令参数
     *
     * @return array
     */
    protected function getOptions()
    {
        return [];
    }
}
