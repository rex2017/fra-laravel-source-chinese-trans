<?php
/**
 * 契约，管道接口
 */

namespace Illuminate\Contracts\Pipeline;

use Closure;

interface Pipeline
{
    /**
     * Set the traveler object being sent on the pipeline.
	 * 在管道上发送对象
     *
     * @param  mixed  $traveler
     * @return $this
     */
    public function send($traveler);

    /**
     * Set the stops of the pipeline.
	 * 设置管道的止水带
     *
     * @param  dynamic|array  $stops
     * @return $this
     */
    public function through($stops);

    /**
     * Set the method to call on the stops.
	 * 设置该方法在停止时调用
     *
     * @param  string  $method
     * @return $this
     */
    public function via($method);

    /**
     * Run the pipeline with a final destination callback.
	 * 运行带有最终目的地回调的管道
     *
     * @param  \Closure  $destination
     * @return mixed
     */
    public function then(Closure $destination);
}
