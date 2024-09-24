<?php
/**
 * 数据库播种抽象类
 */

namespace Illuminate\Database;

use Illuminate\Console\Command;
use Illuminate\Container\Container;
use Illuminate\Support\Arr;
use InvalidArgumentException;

abstract class Seeder
{
    /**
     * The container instance.
	 * 容器实例
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * The console command instance.
	 * 控制台命令实例
     *
     * @var \Illuminate\Console\Command
     */
    protected $command;

    /**
     * Seed the given connection from the given path.
	 * 从给定路径为给定连接播种
     *
     * @param  array|string  $class
     * @param  bool  $silent
     * @return $this
     */
    public function call($class, $silent = false)
    {
        $classes = Arr::wrap($class);

        foreach ($classes as $class) {
            $seeder = $this->resolve($class);

            $name = get_class($seeder);

            if ($silent === false && isset($this->command)) {
                $this->command->getOutput()->writeln("<comment>Seeding:</comment> {$name}");
            }

            $startTime = microtime(true);

            $seeder->__invoke();

            $runTime = round(microtime(true) - $startTime, 2);

            if ($silent === false && isset($this->command)) {
                $this->command->getOutput()->writeln("<info>Seeded:</info>  {$name} ({$runTime} seconds)");
            }
        }

        return $this;
    }

    /**
     * Silently seed the given connection from the given path.
	 * 从给定路径静默地播种给定连接
     *
     * @param  array|string  $class
     * @return void
     */
    public function callSilent($class)
    {
        $this->call($class, true);
    }

    /**
     * Resolve an instance of the given seeder class.
	 * 解析给定种子类的实例
     *
     * @param  string  $class
     * @return \Illuminate\Database\Seeder
     */
    protected function resolve($class)
    {
        if (isset($this->container)) {
            $instance = $this->container->make($class);

            $instance->setContainer($this->container);
        } else {
            $instance = new $class;
        }

        if (isset($this->command)) {
            $instance->setCommand($this->command);
        }

        return $instance;
    }

    /**
     * Set the IoC container instance.
	 * 设置IoC容器实例
     *
     * @param  \Illuminate\Container\Container  $container
     * @return $this
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Set the console command instance.
	 * 设置控制台
     *
     * @param  \Illuminate\Console\Command  $command
     * @return $this
     */
    public function setCommand(Command $command)
    {
        $this->command = $command;

        return $this;
    }

    /**
     * Run the database seeds.
	 * 运行数据库种子
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function __invoke()
    {
        if (! method_exists($this, 'run')) {
            throw new InvalidArgumentException('Method [run] missing from '.get_class($this));
        }

        return isset($this->container)
                    ? $this->container->call([$this, 'run'])
                    : $this->run();
    }
}
