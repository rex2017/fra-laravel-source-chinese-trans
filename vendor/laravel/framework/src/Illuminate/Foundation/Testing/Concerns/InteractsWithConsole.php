<?php
/**
 * 基础，与控制台交互
 */

namespace Illuminate\Foundation\Testing\Concerns;

use Illuminate\Console\OutputStyle;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\PendingCommand;
use Illuminate\Support\Arr;

trait InteractsWithConsole
{
    /**
     * Indicates if the console output should be mocked.
	 * 确定是否应该模拟控制台输出
     *
     * @var bool
     */
    public $mockConsoleOutput = true;

    /**
     * All of the expected output lines.
	 * 所有期望的输出行
     *
     * @var array
     */
    public $expectedOutput = [];

    /**
     * All of the expected questions.
	 * 所有预期的问题
     *
     * @var array
     */
    public $expectedQuestions = [];

    /**
     * Call artisan command and return code.
	 * 调用artisan命令并返回代码
     *
     * @param  string  $command
     * @param  array  $parameters
     * @return \Illuminate\Foundation\Testing\PendingCommand|int
     */
    public function artisan($command, $parameters = [])
    {
        if (! $this->mockConsoleOutput) {
            return $this->app[Kernel::class]->call($command, $parameters);
        }

        $this->beforeApplicationDestroyed(function () {
            if (count($this->expectedQuestions)) {
                $this->fail('Question "'.Arr::first($this->expectedQuestions)[0].'" was not asked.');
            }

            if (count($this->expectedOutput)) {
                $this->fail('Output "'.Arr::first($this->expectedOutput).'" was not printed.');
            }
        });

        return new PendingCommand($this, $this->app, $command, $parameters);
    }

    /**
     * Disable mocking the console output.
	 * 禁用模拟控制台输出
     *
     * @return $this
     */
    protected function withoutMockingConsoleOutput()
    {
        $this->mockConsoleOutput = false;

        $this->app->offsetUnset(OutputStyle::class);

        return $this;
    }
}
