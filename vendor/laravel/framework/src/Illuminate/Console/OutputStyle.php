<?php
/**
 * 控制台输出样式
 */

namespace Illuminate\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class OutputStyle extends SymfonyStyle
{
    /**
     * The output instance.
	 * 输出实例
     *
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    private $output;

    /**
     * Create a new Console OutputStyle instance.
	 * 创建新的控制台输出样式实例
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        parent::__construct($input, $output);
    }

    /**
     * Returns whether verbosity is quiet (-q).
	 * 返回verbose是否为quiet (-q)
     *
     * @return bool
     */
    public function isQuiet()
    {
        return $this->output->isQuiet();
    }

    /**
     * Returns whether verbosity is verbose (-v).
	 * 返回verbose是否为verbose (-v)
     *
     * @return bool
     */
    public function isVerbose()
    {
        return $this->output->isVerbose();
    }

    /**
     * Returns whether verbosity is very verbose (-vv).
	 * 返回verbose是否非常verbose (-vv)
     *
     * @return bool
     */
    public function isVeryVerbose()
    {
        return $this->output->isVeryVerbose();
    }

    /**
     * Returns whether verbosity is debug (-vvv).
	 * 返回verbose是否为debug
     *
     * @return bool
     */
    public function isDebug()
    {
        return $this->output->isDebug();
    }
}
