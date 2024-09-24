<?php
/**
 * Http，响应
 */

namespace Illuminate\Http;

use ArrayObject;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Traits\Macroable;
use JsonSerializable;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

class Response extends BaseResponse
{
    use ResponseTrait, Macroable {
        Macroable::__call as macroCall;
    }

    /**
     * Set the content on the response.
	 * 设置响应内容
     *
     * @param  mixed  $content
     * @return $this
     */
    public function setContent($content)
    {
        $this->original = $content;

        // If the content is "JSONable" we will set the appropriate header and convert
        // the content to JSON. This is useful when returning something like models
        // from routes that will be automatically transformed to their JSON form.
		// 如果内容是"JJSONable"J的，我们将设置适当的标头并将内容转换为JSON。
		// 当从路由返回类似模型的东西时，这很有用，这些模型将自动转换为JSON格式。
        if ($this->shouldBeJson($content)) {
            $this->header('Content-Type', 'application/json');

            $content = $this->morphToJson($content);
        }

        // If this content implements the "Renderable" interface then we will call the
        // render method on the object so we will avoid any "__toString" exceptions
        // that might be thrown and have their errors obscured by PHP's handling.
		// 如果此内容实现了"Renderable"接口，那么我们将调用对象的render方法，
		// 这样我们就可以避免任何可能引发的“__toString”异常，并通过PHP的处理来掩盖它们的错误。
        elseif ($content instanceof Renderable) {
            $content = $content->render();
        }

        parent::setContent($content);

        return $this;
    }

    /**
     * Determine if the given content should be turned into JSON.
	 * 确定是否应该将给定的内容转换为JSON
     *
     * @param  mixed  $content
     * @return bool
     */
    protected function shouldBeJson($content)
    {
        return $content instanceof Arrayable ||
               $content instanceof Jsonable ||
               $content instanceof ArrayObject ||
               $content instanceof JsonSerializable ||
               is_array($content);
    }

    /**
     * Morph the given content into JSON.
	 * 转换给定内容为JSON
     *
     * @param  mixed  $content
     * @return string
     */
    protected function morphToJson($content)
    {
        if ($content instanceof Jsonable) {
            return $content->toJson();
        } elseif ($content instanceof Arrayable) {
            return json_encode($content->toArray());
        }

        return json_encode($content);
    }
}
