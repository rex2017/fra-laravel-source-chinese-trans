<?php
/**
 * 路由视图控制器
 */

namespace Illuminate\Routing;

use Illuminate\Contracts\View\Factory as ViewFactory;

class ViewController extends Controller
{
    /**
     * The view factory implementation.
	 * 视图工厂实现
     *
     * @var \Illuminate\Contracts\View\Factory
     */
    protected $view;

    /**
     * Create a new controller instance.
	 * 创建新的控制器实例
     *
     * @param  \Illuminate\Contracts\View\Factory  $view
     * @return void
     */
    public function __construct(ViewFactory $view)
    {
        $this->view = $view;
    }

    /**
     * Invoke the controller method.
	 * 调用控制器方法
     *
     * @param  array  $args
     * @return \Illuminate\Contracts\View\View
     */
    public function __invoke(...$args)
    {
        [$view, $data] = array_slice($args, -2);

        return $this->view->make($view, $data);
    }
}
