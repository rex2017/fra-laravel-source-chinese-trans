<?php
/**
 * 基础，等待中命令
 */

namespace Illuminate\Foundation\Testing;

use Illuminate\Console\OutputStyle;
use Illuminate\Contracts\Console\Kernel;
use Mockery;
use Mockery\Exception\NoMatchingExpectationException;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\Output;

class PendingCommand
{
    /**
     * The test being run.
	 * 正进行测试
     *
     * @var \Illuminate\Foundation\Testing\TestCase
     */
    public $test;

    /**
     * The application instance.
	 * 应用实例
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * The command to run.
	 * 运行命令
     *
     * @var string
     */
    protected $command;

    /**
     * The parameters to pass to the command.
	 * 要传递给命令的参数
     *
     * @var array
     */
    protected $parameters;

    /**
     * The expected exit code.
	 * 预期的退出代码
     *
     * @var int
     */
    protected $expectedExitCode;

    /**
     * Determine if command has executed.
	 * 定义是否命令已执行
     *
     * @var bool
     */
    protected $hasExecuted = false;

    /**
     * Create a new pending console command run.
	 * 创建新的的暂挂控制台命令运行
     *
     * @param  \PHPUnit\Framework\TestCase  $test
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  string  $command
     * @param  array  $parameters
     * @return void
     */
    public function __construct(PHPUnitTestCase $test, $app, $command, $parameters)
    {
        $this->app = $app;
        $this->test = $test;
        $this->command = $command;
        $this->parameters = $parameters;
    }

    /**
     * Specify a question that should be asked when the command runs.
	 * 指定命令运行时应该询问的问题
     *
     * @param  string  $question
     * @param  string  $answer
     * @return $this
     */
    public function expectsQuestion($question, $answer)
    {
        $this->test->expectedQuestions[] = [$question, $answer];

        return $this;
    }

    /**
     * Specify output that should be printed when the command runs.
	 * 指定命令运行时应该打印的输出
     *
     * @param  string  $output
     * @return $this
     */
    public function expectsOutput($output)
    {
        $this->test->expectedOutput[] = $output;

        return $this;
    }

    /**
     * Assert that the command has the given exit code.
	 * 断言该命令具有给定的退出代码
     *
     * @param  int  $exitCode
     * @return $this
     */
    public function assertExitCode($exitCode)
    {
        $this->expectedExitCode = $exitCode;

        return $this;
    }

    /**
     * Execute the command.
	 * 执行命令
     *
     * @return int
     */
    public function execute()
    {
        return $this->run();
    }

    /**
     * Execute the command.
	 * 执行命令
     *
     * @return int
     */
    public function run()
    {
        $this->hasExecuted = true;

        $mock = $this->mockConsoleOutput();

        try {
            $exitCode = $this->app[Kernel::class]->call($this->command, $this->parameters, $mock);
        } catch (NoMatchingExpectationException $e) {
            if ($e->getMethodName() === 'askQuestion') {
                $this->test->fail('Unexpected question "'.$e->getActualArguments()[0]->getQuestion().'" was asked.');
            }

            throw $e;
        }

        if ($this->expectedExitCode !== null) {
            $this->test->assertEquals(
                $this->expectedExitCode, $exitCode,
                "Expected status code {$this->expectedExitCode} but received {$exitCode}."
            );
        }

        return $exitCode;
    }

    /**
     * Mock the application's console output.
	 * 模拟应用程序的控制台输出
     *
     * @return \Mockery\MockInterface
     */
    protected function mockConsoleOutput()
    {
        $mock = Mockery::mock(OutputStyle::class.'[askQuestion]', [
            (new ArrayInput($this->parameters)), $this->createABufferedOutputMock(),
        ]);

        foreach ($this->test->expectedQuestions as $i => $question) {
            $mock->shouldReceive('askQuestion')
                ->once()
                ->ordered()
                ->with(Mockery::on(function ($argument) use ($question) {
                    return $argument->getQuestion() == $question[0];
                }))
                ->andReturnUsing(function () use ($question, $i) {
                    unset($this->test->expectedQuestions[$i]);

                    return $question[1];
                });
        }

        $this->app->bind(OutputStyle::class, function () use ($mock) {
            return $mock;
        });

        return $mock;
    }

    /**
     * Create a mock for the buffered output.
	 * 创建一个模拟为缓冲的输出
     *
     * @return \Mockery\MockInterface
     */
    private function createABufferedOutputMock()
    {
        $mock = Mockery::mock(BufferedOutput::class.'[doWrite]')
                ->shouldAllowMockingProtectedMethods()
                ->shouldIgnoreMissing();

        foreach ($this->test->expectedOutput as $i => $output) {
            $mock->shouldReceive('doWrite')
                ->once()
                ->ordered()
                ->with($output, Mockery::any())
                ->andReturnUsing(function () use ($i) {
                    unset($this->test->expectedOutput[$i]);
                });
        }

        return $mock;
    }

    /**
     * Handle the object's destruction.
	 * 处理对象的销毁
     *
     * @return void
     */
    public function __destruct()
    {
        if ($this->hasExecuted) {
            return;
        }

        $this->run();
    }
}
