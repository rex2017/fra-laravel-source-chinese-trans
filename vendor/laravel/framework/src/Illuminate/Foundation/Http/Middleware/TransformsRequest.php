<?php
/**
 * 基础，Http中间件，转换请求
 */

namespace Illuminate\Foundation\Http\Middleware;

use Closure;
use Symfony\Component\HttpFoundation\ParameterBag;

class TransformsRequest
{
    /**
     * Handle an incoming request.
	 * 处理传入请求
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $this->clean($request);

        return $next($request);
    }

    /**
     * Clean the request's data.
	 * 清除请求数据
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function clean($request)
    {
        $this->cleanParameterBag($request->query);

        if ($request->isJson()) {
            $this->cleanParameterBag($request->json());
        } elseif ($request->request !== $request->query) {
            $this->cleanParameterBag($request->request);
        }
    }

    /**
     * Clean the data in the parameter bag.
	 * 清理参数包中的数据
     *
     * @param  \Symfony\Component\HttpFoundation\ParameterBag  $bag
     * @return void
     */
    protected function cleanParameterBag(ParameterBag $bag)
    {
        $bag->replace($this->cleanArray($bag->all()));
    }

    /**
     * Clean the data in the given array.
	 * 清理给定数组的数据
     *
     * @param  array  $data
     * @param  string  $keyPrefix
     * @return array
     */
    protected function cleanArray(array $data, $keyPrefix = '')
    {
        return collect($data)->map(function ($value, $key) use ($keyPrefix) {
            return $this->cleanValue($keyPrefix.$key, $value);
        })->all();
    }

    /**
     * Clean the given value.
	 * 清除给定值
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function cleanValue($key, $value)
    {
        if (is_array($value)) {
            return $this->cleanArray($value, $key.'.');
        }

        return $this->transform($key, $value);
    }

    /**
     * Transform the given value.
	 * 变换给定值
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function transform($key, $value)
    {
        return $value;
    }
}
