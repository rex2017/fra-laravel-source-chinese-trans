<?php
/**
 * 分页抽象类
 */

namespace Illuminate\Pagination;

use Closure;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;

/**
 * @mixin \Illuminate\Support\Collection
 */
abstract class AbstractPaginator implements Htmlable
{
    use ForwardsCalls;

    /**
     * All of the items being paginated.
	 * 所有被分页项
     *
     * @var \Illuminate\Support\Collection
     */
    protected $items;

    /**
     * The number of items to be shown per page.
	 * 每页显示数
     *
     * @var int
     */
    protected $perPage;

    /**
     * The current page being "viewed".
	 * 当前页
     *
     * @var int
     */
    protected $currentPage;

    /**
     * The base path to assign to all URLs.
	 * 分配给所有url的基本路径
     *
     * @var string
     */
    protected $path = '/';

    /**
     * The query parameters to add to all URLs.
	 * 要添加到所有url的查询参数
     *
     * @var array
     */
    protected $query = [];

    /**
     * The URL fragment to add to all URLs.
	 * 要添加到所有URL的URL片段
     *
     * @var string|null
     */
    protected $fragment;

    /**
     * The query string variable used to store the page.
	 * 用于存储页面的查询字符串变量
     *
     * @var string
     */
    protected $pageName = 'page';

    /**
     * The number of links to display on each side of current page link.
	 * 要在当前页面链接的每一边显示的链接数
     *
     * @var int
     */
    public $onEachSide = 3;

    /**
     * The paginator options.
	 * 分页器选项
     *
     * @var array
     */
    protected $options;

    /**
     * The current path resolver callback.
	 * 当前路径解析器回调
     *
     * @var \Closure
     */
    protected static $currentPathResolver;

    /**
     * The current page resolver callback.
	 * 当前页面解析器回调
     *
     * @var \Closure
     */
    protected static $currentPageResolver;

    /**
     * The view factory resolver callback.
	 * 视图工厂解析器回调
     *
     * @var \Closure
     */
    protected static $viewFactoryResolver;

    /**
     * The default pagination view.
	 * 默认分页视图
     *
     * @var string
     */
    public static $defaultView = 'pagination::bootstrap-4';

    /**
     * The default "simple" pagination view.
	 * 默认的"简单"分页视图
     *
     * @var string
     */
    public static $defaultSimpleView = 'pagination::simple-bootstrap-4';

    /**
     * Determine if the given value is a valid page number.
	 * 确定给定的值是否是有效的页码
     *
     * @param  int  $page
     * @return bool
     */
    protected function isValidPageNumber($page)
    {
        return $page >= 1 && filter_var($page, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Get the URL for the previous page.
	 * 获取前一页的URL
     *
     * @return string|null
     */
    public function previousPageUrl()
    {
        if ($this->currentPage() > 1) {
            return $this->url($this->currentPage() - 1);
        }
    }

    /**
     * Create a range of pagination URLs.
	 * 创建一系列分页URL
     *
     * @param  int  $start
     * @param  int  $end
     * @return array
     */
    public function getUrlRange($start, $end)
    {
        return collect(range($start, $end))->mapWithKeys(function ($page) {
            return [$page => $this->url($page)];
        })->all();
    }

    /**
     * Get the URL for a given page number.
	 * 得到给定页码的URL
     *
     * @param  int  $page
     * @return string
     */
    public function url($page)
    {
        if ($page <= 0) {
            $page = 1;
        }

        // If we have any extra query string key / value pairs that need to be added
        // onto the URL, we will put them in query string form and then attach it
        // to the URL. This allows for extra information like sortings storage.
		// 如果我们有任何额外的查询字符串键/值对需要添加到URL上，我们会将它们以查询字符串的形式放置，
		// 然后将其附加到URL上。这允许存储排序等额外信息。
        $parameters = [$this->pageName => $page];

        if (count($this->query) > 0) {
            $parameters = array_merge($this->query, $parameters);
        }

        return $this->path()
                        .(Str::contains($this->path(), '?') ? '&' : '?')
                        .Arr::query($parameters)
                        .$this->buildFragment();
    }

    /**
     * Get / set the URL fragment to be appended to URLs.
	 * 得到或设置要附加到URL的URL片段
     *
     * @param  string|null  $fragment
     * @return $this|string|null
     */
    public function fragment($fragment = null)
    {
        if (is_null($fragment)) {
            return $this->fragment;
        }

        $this->fragment = $fragment;

        return $this;
    }

    /**
     * Add a set of query string values to the paginator.
	 * 添加一组查询字符串值至分页器
     *
     * @param  array|string|null  $key
     * @param  string|null  $value
     * @return $this
     */
    public function appends($key, $value = null)
    {
        if (is_null($key)) {
            return $this;
        }

        if (is_array($key)) {
            return $this->appendArray($key);
        }

        return $this->addQuery($key, $value);
    }

    /**
     * Add an array of query string values.
	 * 添加查询字符串值数组
     *
     * @param  array  $keys
     * @return $this
     */
    protected function appendArray(array $keys)
    {
        foreach ($keys as $key => $value) {
            $this->addQuery($key, $value);
        }

        return $this;
    }

    /**
     * Add a query string value to the paginator.
	 * 添加查询字符串值至分页器
     *
     * @param  string  $key
     * @param  string  $value
     * @return $this
     */
    protected function addQuery($key, $value)
    {
        if ($key !== $this->pageName) {
            $this->query[$key] = $value;
        }

        return $this;
    }

    /**
     * Build the full fragment portion of a URL.
	 * 构建URL的完整片段部分
     *
     * @return string
     */
    protected function buildFragment()
    {
        return $this->fragment ? '#'.$this->fragment : '';
    }

    /**
     * Load a set of relationships onto the mixed relationship collection.
	 * 加载一组关系到混合关系集合中
     *
     * @param  string  $relation
     * @param  array  $relations
     * @return $this
     */
    public function loadMorph($relation, $relations)
    {
        $this->getCollection()->loadMorph($relation, $relations);

        return $this;
    }

    /**
     * Get the slice of items being paginated.
	 * 得到正在分页的项的切片
     *
     * @return array
     */
    public function items()
    {
        return $this->items->all();
    }

    /**
     * Get the number of the first item in the slice.
	 * 得到切片中第一项的编号
     *
     * @return int
     */
    public function firstItem()
    {
        return count($this->items) > 0 ? ($this->currentPage - 1) * $this->perPage + 1 : null;
    }

    /**
     * Get the number of the last item in the slice.
	 * 得到切片中最后一项的编号
     *
     * @return int
     */
    public function lastItem()
    {
        return count($this->items) > 0 ? $this->firstItem() + $this->count() - 1 : null;
    }

    /**
     * Get the number of items shown per page.
	 * 得到每页显示的项目数
     *
     * @return int
     */
    public function perPage()
    {
        return $this->perPage;
    }

    /**
     * Determine if there are enough items to split into multiple pages.
	 * 确定是否有足够的项目可以拆分为多个页面
     *
     * @return bool
     */
    public function hasPages()
    {
        return $this->currentPage() != 1 || $this->hasMorePages();
    }

    /**
     * Determine if the paginator is on the first page.
	 * 确定分页器是否在第一页上
     *
     * @return bool
     */
    public function onFirstPage()
    {
        return $this->currentPage() <= 1;
    }

    /**
     * Get the current page.
	 * 得到当前页
     *
     * @return int
     */
    public function currentPage()
    {
        return $this->currentPage;
    }

    /**
     * Get the query string variable used to store the page.
	 * 得到用于存储该页的查询字符串变量
     *
     * @return string
     */
    public function getPageName()
    {
        return $this->pageName;
    }

    /**
     * Set the query string variable used to store the page.
	 * 设置用于存储页面的查询字符串变量
     *
     * @param  string  $name
     * @return $this
     */
    public function setPageName($name)
    {
        $this->pageName = $name;

        return $this;
    }

    /**
     * Set the base path to assign to all URLs.
	 * 设置分配给所有URL的基本路径
     *
     * @param  string  $path
     * @return $this
     */
    public function withPath($path)
    {
        return $this->setPath($path);
    }

    /**
     * Set the base path to assign to all URLs.
	 * 设置分配给所有URL的基本路径
     *
     * @param  string  $path
     * @return $this
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Set the number of links to display on each side of current page link.
	 * 设置要在当前页面链接的每一边显示的链接数
     *
     * @param  int  $count
     * @return $this
     */
    public function onEachSide($count)
    {
        $this->onEachSide = $count;

        return $this;
    }

    /**
     * Get the base path for paginator generated URLs.
	 * 得到分页器生成的url的基本路径
     *
     * @return string|null
     */
    public function path()
    {
        return $this->path;
    }

    /**
     * Resolve the current request path or return the default value.
	 * 解析当前请求路径或返回默认值
     *
     * @param  string  $default
     * @return string
     */
    public static function resolveCurrentPath($default = '/')
    {
        if (isset(static::$currentPathResolver)) {
            return call_user_func(static::$currentPathResolver);
        }

        return $default;
    }

    /**
     * Set the current request path resolver callback.
	 * 设置当前请求路径解析器回调
     *
     * @param  \Closure  $resolver
     * @return void
     */
    public static function currentPathResolver(Closure $resolver)
    {
        static::$currentPathResolver = $resolver;
    }

    /**
     * Resolve the current page or return the default value.
	 * 解析当前页面或返回默认值
     *
     * @param  string  $pageName
     * @param  int  $default
     * @return int
     */
    public static function resolveCurrentPage($pageName = 'page', $default = 1)
    {
        if (isset(static::$currentPageResolver)) {
            return (int) call_user_func(static::$currentPageResolver, $pageName);
        }

        return $default;
    }

    /**
     * Set the current page resolver callback.
	 * 设置当前页面解析器回调
     *
     * @param  \Closure  $resolver
     * @return void
     */
    public static function currentPageResolver(Closure $resolver)
    {
        static::$currentPageResolver = $resolver;
    }

    /**
     * Get an instance of the view factory from the resolver.
	 * 得到视图工厂的实例从解析器
     *
     * @return \Illuminate\Contracts\View\Factory
     */
    public static function viewFactory()
    {
        return call_user_func(static::$viewFactoryResolver);
    }

    /**
     * Set the view factory resolver callback.
	 * 设置视图工厂解析器回调
     *
     * @param  \Closure  $resolver
     * @return void
     */
    public static function viewFactoryResolver(Closure $resolver)
    {
        static::$viewFactoryResolver = $resolver;
    }

    /**
     * Set the default pagination view.
	 * 设置默认分页器视图
     *
     * @param  string  $view
     * @return void
     */
    public static function defaultView($view)
    {
        static::$defaultView = $view;
    }

    /**
     * Set the default "simple" pagination view.
	 * 设置默认的"simple"分页视图
     *
     * @param  string  $view
     * @return void
     */
    public static function defaultSimpleView($view)
    {
        static::$defaultSimpleView = $view;
    }

    /**
     * Indicate that Bootstrap 3 styling should be used for generated links.
	 * 指明生成的链接应该使用Bootstrap 3样式
     *
     * @return void
     */
    public static function useBootstrapThree()
    {
        static::defaultView('pagination::default');
        static::defaultSimpleView('pagination::simple-default');
    }

    /**
     * Get an iterator for the items.
	 * 得到项的迭代器
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return $this->items->getIterator();
    }

    /**
     * Determine if the list of items is empty.
	 * 确定项目列表是否为空
     *
     * @return bool
     */
    public function isEmpty()
    {
        return $this->items->isEmpty();
    }

    /**
     * Determine if the list of items is not empty.
	 * 确定项目列表是否不为空
     *
     * @return bool
     */
    public function isNotEmpty()
    {
        return $this->items->isNotEmpty();
    }

    /**
     * Get the number of items for the current page.
	 * 得到当前页面的项数
     *
     * @return int
     */
    public function count()
    {
        return $this->items->count();
    }

    /**
     * Get the paginator's underlying collection.
	 * 得到分页器的底层集合
     *
     * @return \Illuminate\Support\Collection
     */
    public function getCollection()
    {
        return $this->items;
    }

    /**
     * Set the paginator's underlying collection.
	 * 设置分页器的底层集合
     *
     * @param  \Illuminate\Support\Collection  $collection
     * @return $this
     */
    public function setCollection(Collection $collection)
    {
        $this->items = $collection;

        return $this;
    }

    /**
     * Get the paginator options.
	 * 得到分页器的选项
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Determine if the given item exists.
	 * 确定给定项是否存在
     *
     * @param  mixed  $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->items->has($key);
    }

    /**
     * Get the item at the given offset.
	 * 得到给定偏移量处的项
     *
     * @param  mixed  $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->items->get($key);
    }

    /**
     * Set the item at the given offset.
	 * 设置项在给定的偏移量处
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        $this->items->put($key, $value);
    }

    /**
     * Unset the item at the given key.
	 * 取消给定键处的项设置
     *
     * @param  mixed  $key
     * @return void
     */
    public function offsetUnset($key)
    {
        $this->items->forget($key);
    }

    /**
     * Render the contents of the paginator to HTML.
	 * 呈现分页器的内容为HTML
     *
     * @return string
     */
    public function toHtml()
    {
        return (string) $this->render();
    }

    /**
     * Make dynamic calls into the collection.
	 * 对集合进行动态调用
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->forwardCallTo($this->getCollection(), $method, $parameters);
    }

    /**
     * Render the contents of the paginator when casting to string.
	 * 呈现分页器的内容在转换为字符串时
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->render();
    }
}
