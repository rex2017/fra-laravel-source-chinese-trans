<?php
/**
 * 视图，来自会话的共享错误
 */

namespace Illuminate\View\Middleware;

use Closure;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\ViewErrorBag;

class ShareErrorsFromSession
{
    /**
     * The view factory implementation.
	 * 视图工厂实现
     *
     * @var \Illuminate\Contracts\View\Factory
     */
    protected $view;

    /**
     * Create a new error binder instance.
	 * 创建一个新的错误绑定实例
     *
     * @param  \Illuminate\Contracts\View\Factory  $view
     * @return void
     */
    public function __construct(ViewFactory $view)
    {
        $this->view = $view;
    }

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
        // If the current session has an "errors" variable bound to it, we will share
        // its value with all view instances so the views can easily access errors
        // without having to bind. An empty bag is set when there aren't errors.
		// 如果当前会话绑定了"errors"变量，我们将与所有视图实例共享其值，
		// 以便视图可以轻松访问错误而无需绑定。当没有错误时，会设置一个空包。
        $this->view->share(
            'errors', $request->session()->get('errors') ?: new ViewErrorBag
        );

        // Putting the errors in the view for every view allows the developer to just
        // assume that some errors are always available, which is convenient since
        // they don't have to continually run checks for the presence of errors.
		// 将错误放在每个视图的视图中，允许开发人员假设某些错误始终可用，
		// 这很方便，因为他们不必不断地检查是否存在错误。

        return $next($request);
    }
}
