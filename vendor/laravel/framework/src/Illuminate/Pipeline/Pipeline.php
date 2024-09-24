<?php
/**
 * 管道，核心类
 */

namespace Illuminate\Pipeline;

use Closure;
use Exception;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Pipeline\Pipeline as PipelineContract;
use RuntimeException;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Throwable;

class Pipeline implements PipelineContract
{
    /**
     * The container implementation.
	 * 容器实现
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * The object being passed through the pipeline.
	 * 对象正在通过管道传递
     *
     * @var mixed
     */
    protected $passable;

    /**
     * The array of class pipes.
	 * 管道类
     *
     * @var array
     */
    protected $pipes = [];

    /**
     * The method to call on each pipe.
	 * 方法调取管道的
     *
     * @var string
     */
    protected $method = 'handle';

    /**
     * Create a new class instance.
	 * 创建新的类实例
     *
     * @param  \Illuminate\Contracts\Container\Container|null  $container
     * @return void
     */
    public function __construct(Container $container = null)
    {
        $this->container = $container;
    }

    /**
     * Set the object being sent through the pipeline.
	 * 设置通过管道发送的对象
     *
     * @param  mixed  $passable
     * @return $this
     */
    public function send($passable)
    {
        $this->passable = $passable;

        return $this;
    }

    /**
     * Set the array of pipes.
	 * 设置管道
     *
     * @param  array|mixed  $pipes
     * @return $this
     */
    public function through($pipes)
    {
        $this->pipes = is_array($pipes) ? $pipes : func_get_args();

        return $this;
    }

    /**
     * Set the method to call on the pipes.
	 * 设置要在管道上调用的方法
     *
     * @param  string  $method
     * @return $this
     */
    public function via($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Run the pipeline with a final destination callback.
	 * 运行带有最终目的地回调的管道
     *
     * @param  \Closure  $destination
     * @return mixed
     */
    public function then(Closure $destination)
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes()), $this->carry(), $this->prepareDestination($destination)
        );

        return $pipeline($this->passable);
    }

    /**
     * Run the pipeline and return the result.
	 * 运行管道且返回结果
     *
     * @return mixed
     */
    public function thenReturn()
    {
        return $this->then(function ($passable) {
            return $passable;
        });
    }

    /**
     * Get the final piece of the Closure onion.
	 * 得到最终闭包
     *
     * @param  \Closure  $destination
     * @return \Closure
     */
    protected function prepareDestination(Closure $destination)
    {
        return function ($passable) use ($destination) {
            try {
                return $destination($passable);
            } catch (Exception $e) {
                return $this->handleException($passable, $e);
            } catch (Throwable $e) {
                return $this->handleException($passable, new FatalThrowableError($e));
            }
        };
    }

    /**
     * Get a Closure that represents a slice of the application onion.
	 * 得到闭包
     *
     * @return \Closure
     */
    protected function carry()
    {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                try {
                    if (is_callable($pipe)) {
                        // If the pipe is a callable, then we will call it directly, but otherwise we
                        // will resolve the pipes out of the dependency container and call it with
                        // the appropriate method and arguments, returning the results back out.
						// 如果管道是可调用的，那么我们将直接调用它，否则我们将从依赖容器中解析管道，
						// 并使用适当的方法和参数调用它，返回结果。
                        return $pipe($passable, $stack);
                    } elseif (! is_object($pipe)) {
                        [$name, $parameters] = $this->parsePipeString($pipe);

                        // If the pipe is a string we will parse the string and resolve the class out
                        // of the dependency injection container. We can then build a callable and
                        // execute the pipe function giving in the parameters that are required.
						// 如果管道是字符串，我们将解析字符串并将类从依赖注入容器中解析出来。
						// 然后，我们可以构建一个可调用函数，并执行管道函数，给出所需的参数。
                        $pipe = $this->getContainer()->make($name);

                        $parameters = array_merge([$passable, $stack], $parameters);
                    } else {
                        // If the pipe is already an object we'll just make a callable and pass it to
                        // the pipe as-is. There is no need to do any extra parsing and formatting
                        // since the object we're given was already a fully instantiated object.
						// 如果管道已经是一个对象，我们只需创建一个可调用对象并按原样将其传递给管道。
						// 不需要进行任何额外的解析和格式化，因为我们给出的对象已经是一种完全实例化的对象。
                        $parameters = [$passable, $stack];
                    }

                    $carry = method_exists($pipe, $this->method)
                                    ? $pipe->{$this->method}(...$parameters)
                                    : $pipe(...$parameters);

                    return $this->handleCarry($carry);
                } catch (Exception $e) {
                    return $this->handleException($passable, $e);
                } catch (Throwable $e) {
                    return $this->handleException($passable, new FatalThrowableError($e));
                }
            };
        };
    }

    /**
     * Parse full pipe string to get name and parameters.
	 * 解析完整的管道字符串以获取名称和参数
     *
     * @param  string  $pipe
     * @return array
     */
    protected function parsePipeString($pipe)
    {
        [$name, $parameters] = array_pad(explode(':', $pipe, 2), 2, []);

        if (is_string($parameters)) {
            $parameters = explode(',', $parameters);
        }

        return [$name, $parameters];
    }

    /**
     * Get the array of configured pipes.
	 * 得到已配置管道的数组
     *
     * @return array
     */
    protected function pipes()
    {
        return $this->pipes;
    }

    /**
     * Get the container instance.
	 * 得到容器实例
     *
     * @return \Illuminate\Contracts\Container\Container
     *
     * @throws \RuntimeException
     */
    protected function getContainer()
    {
        if (! $this->container) {
            throw new RuntimeException('A container instance has not been passed to the Pipeline.');
        }

        return $this->container;
    }

    /**
     * Handle the value returned from each pipe before passing it to the next.
	 * 在将每个管道返回的值传递给下一个管道之前处理它
     *
     * @param  mixed  $carry
     * @return mixed
     */
    protected function handleCarry($carry)
    {
        return $carry;
    }

    /**
     * Handle the given exception.
	 * 处理给定异常
     *
     * @param  mixed  $passable
     * @param  \Exception  $e
     * @return mixed
     *
     * @throws \Exception
     */
    protected function handleException($passable, Exception $e)
    {
        throw $e;
    }
}
