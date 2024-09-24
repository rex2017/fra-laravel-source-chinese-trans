<?php
/**
 * 契约，响应能力接口
 */

namespace Illuminate\Contracts\Support;

interface Responsable
{
    /**
     * Create an HTTP response that represents the object.
	 * 创建一个http响应
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request);
}
