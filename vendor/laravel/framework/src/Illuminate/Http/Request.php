<?php
/**
 * Http，Request请求类
 */

namespace Illuminate\Http;

use ArrayAccess;
use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use RuntimeException;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

/**
 * @method array validate(array $rules, ...$params)
 * @method array validateWithBag(string $errorBag, array $rules, ...$params)
 * @method bool hasValidSignature(bool $absolute = true)
 */
class Request extends SymfonyRequest implements Arrayable, ArrayAccess
{
    use Concerns\InteractsWithContentTypes,
        Concerns\InteractsWithFlashData,
        Concerns\InteractsWithInput,
        Macroable;

    /**
     * The decoded JSON content for the request.
	 * 请求的解码JSON内容
     *
     * @var \Symfony\Component\HttpFoundation\ParameterBag|null
     */
    protected $json;

    /**
     * All of the converted files for the request.
	 * 所有已解析的文件请求
     *
     * @var array
     */
    protected $convertedFiles;

    /**
     * The user resolver callback.
	 * 用户解析回调
     *
     * @var \Closure
     */
    protected $userResolver;

    /**
     * The route resolver callback.
	 * 路由解析回调
     *
     * @var \Closure
     */
    protected $routeResolver;

    /**
     * Create a new Illuminate HTTP request from server variables.
	 * 创建新的照亮HTTP请求从服务器变量
     *
     * @return static
     */
    public static function capture()
    {
        static::enableHttpMethodParameterOverride();

        return static::createFromBase(SymfonyRequest::createFromGlobals());
    }

    /**
     * Return the Request instance.
	 * 返回请求实例
     *
     * @return $this
     */
    public function instance()
    {
        return $this;
    }

    /**
     * Get the request method.
	 * 得到请求方法
     *
     * @return string
     */
    public function method()
    {
        return $this->getMethod();
    }

    /**
     * Get the root URL for the application.
	 * 得到根目录URL
     *
     * @return string
     */
    public function root()
    {
        return rtrim($this->getSchemeAndHttpHost().$this->getBaseUrl(), '/');
    }

    /**
     * Get the URL (no query string) for the request.
	 * 得到请求URL
     *
     * @return string
     */
    public function url()
    {
        return rtrim(preg_replace('/\?.*/', '', $this->getUri()), '/');
    }

    /**
     * Get the full URL for the request.
	 * 得到完整请求URL
     *
     * @return string
     */
    public function fullUrl()
    {
        $query = $this->getQueryString();

        $question = $this->getBaseUrl().$this->getPathInfo() === '/' ? '/?' : '?';

        return $query ? $this->url().$question.$query : $this->url();
    }

    /**
     * Get the full URL for the request with the added query string parameters.
	 * 得到请求完成路径
     *
     * @param  array  $query
     * @return string
     */
    public function fullUrlWithQuery(array $query)
    {
        $question = $this->getBaseUrl().$this->getPathInfo() === '/' ? '/?' : '?';

        return count($this->query()) > 0
            ? $this->url().$question.Arr::query(array_merge($this->query(), $query))
            : $this->fullUrl().$question.Arr::query($query);
    }

    /**
     * Get the current path info for the request.
	 * 得到当前请求路径
     *
     * @return string
     */
    public function path()
    {
        $pattern = trim($this->getPathInfo(), '/');

        return $pattern == '' ? '/' : $pattern;
    }

    /**
     * Get the current decoded path info for the request.
	 * 得到当前请求编码路径
     *
     * @return string
     */
    public function decodedPath()
    {
        return rawurldecode($this->path());
    }

    /**
     * Get a segment from the URI (1 based index).
	 * 得到一个段
     *
     * @param  int  $index
     * @param  string|null  $default
     * @return string|null
     */
    public function segment($index, $default = null)
    {
        return Arr::get($this->segments(), $index - 1, $default);
    }

    /**
     * Get all of the segments for the request path.
	 * 得到所有段
     *
     * @return array
     */
    public function segments()
    {
        $segments = explode('/', $this->decodedPath());

        return array_values(array_filter($segments, function ($value) {
            return $value !== '';
        }));
    }

    /**
     * Determine if the current request URI matches a pattern.
	 * 确定是否当前请求URL
     *
     * @param  mixed  ...$patterns
     * @return bool
     */
    public function is(...$patterns)
    {
        $path = $this->decodedPath();

        foreach ($patterns as $pattern) {
            if (Str::is($pattern, $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the route name matches a given pattern.
	 * 确定路由名是否与给定模式匹配
     *
     * @param  mixed  ...$patterns
     * @return bool
     */
    public function routeIs(...$patterns)
    {
        return $this->route() && $this->route()->named(...$patterns);
    }

    /**
     * Determine if the current request URL and query string matches a pattern.
	 * 确定当前请求URL和查询字符串是否与模式匹配
     *
     * @param  mixed  ...$patterns
     * @return bool
     */
    public function fullUrlIs(...$patterns)
    {
        $url = $this->fullUrl();

        foreach ($patterns as $pattern) {
            if (Str::is($pattern, $url)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the request is the result of an AJAX call.
	 * 确定请求是否是AJAX调用的结果
     *
     * @return bool
     */
    public function ajax()
    {
        return $this->isXmlHttpRequest();
    }

    /**
     * Determine if the request is the result of an PJAX call.
	 * 确定请求是否是PJAX调用的结果
     *
     * @return bool
     */
    public function pjax()
    {
        return $this->headers->get('X-PJAX') == true;
    }

    /**
     * Determine if the request is the result of an prefetch call.
	 * 确定请求是否是预取调用的结果
     *
     * @return bool
     */
    public function prefetch()
    {
        return strcasecmp($this->server->get('HTTP_X_MOZ'), 'prefetch') === 0 ||
               strcasecmp($this->headers->get('Purpose'), 'prefetch') === 0;
    }

    /**
     * Determine if the request is over HTTPS.
	 * 确定请求是否通过HTTPS
     *
     * @return bool
     */
    public function secure()
    {
        return $this->isSecure();
    }

    /**
     * Get the client IP address.
	 * 得到客户端IP地址
     *
     * @return string|null
     */
    public function ip()
    {
        return $this->getClientIp();
    }

    /**
     * Get the client IP addresses.
	 * 得到客户端IP地址
     *
     * @return array
     */
    public function ips()
    {
        return $this->getClientIps();
    }

    /**
     * Get the client user agent.
	 * 得到客户端用户代理
     *
     * @return string|null
     */
    public function userAgent()
    {
        return $this->headers->get('User-Agent');
    }

    /**
     * Merge new input into the current request's input array.
	 * 合并新输入到当前请求的输入数组中
     *
     * @param  array  $input
     * @return $this
     */
    public function merge(array $input)
    {
        $this->getInputSource()->add($input);

        return $this;
    }

    /**
     * Replace the input for the current request.
	 * 替换输入为当前请求
     *
     * @param  array  $input
     * @return $this
     */
    public function replace(array $input)
    {
        $this->getInputSource()->replace($input);

        return $this;
    }

    /**
     * This method belongs to Symfony HttpFoundation and is not usually needed when using Laravel.
	 * 这个方法属于Symfony HttpFoundation，在使用Laravel时通常不需要。
     *
     * Instead, you may use the "input" method.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return parent::get($key, $default);
    }

    /**
     * Get the JSON payload for the request.
	 * 得到请求的JSON有效负载
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @return \Symfony\Component\HttpFoundation\ParameterBag|mixed
     */
    public function json($key = null, $default = null)
    {
        if (! isset($this->json)) {
            $this->json = new ParameterBag((array) json_decode($this->getContent(), true));
        }

        if (is_null($key)) {
            return $this->json;
        }

        return data_get($this->json->all(), $key, $default);
    }

    /**
     * Get the input source for the request.
	 * 得到请求输入源
     *
     * @return \Symfony\Component\HttpFoundation\ParameterBag
     */
    protected function getInputSource()
    {
        if ($this->isJson()) {
            return $this->json();
        }

        return in_array($this->getRealMethod(), ['GET', 'HEAD']) ? $this->query : $this->request;
    }

    /**
     * Create a new request instance from the given Laravel request.
	 * 创建新的请求实例从给定请求
     *
     * @param  \Illuminate\Http\Request  $from
     * @param  \Illuminate\Http\Request|null  $to
     * @return static
     */
    public static function createFrom(self $from, $to = null)
    {
        $request = $to ?: new static;

        $files = $from->files->all();

        $files = is_array($files) ? array_filter($files) : $files;

        $request->initialize(
            $from->query->all(),
            $from->request->all(),
            $from->attributes->all(),
            $from->cookies->all(),
            $files,
            $from->server->all(),
            $from->getContent()
        );

        $request->headers->replace($from->headers->all());

        $request->setJson($from->json());

        if ($session = $from->getSession()) {
            $request->setLaravelSession($session);
        }

        $request->setUserResolver($from->getUserResolver());

        $request->setRouteResolver($from->getRouteResolver());

        return $request;
    }

    /**
     * Create an Illuminate request from a Symfony instance.
	 * 创建一个点亮请求从Symfony实例
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return static
     */
    public static function createFromBase(SymfonyRequest $request)
    {
        if ($request instanceof static) {
            return $request;
        }

        $newRequest = (new static)->duplicate(
            $request->query->all(), $request->request->all(), $request->attributes->all(),
            $request->cookies->all(), $request->files->all(), $request->server->all()
        );

        $newRequest->headers->replace($request->headers->all());

        $newRequest->content = $request->content;

        $newRequest->request = $newRequest->getInputSource();

        return $newRequest;
    }

    /**
     * {@inheritdoc}
     */
    public function duplicate(array $query = null, array $request = null, array $attributes = null, array $cookies = null, array $files = null, array $server = null)
    {
        return parent::duplicate($query, $request, $attributes, $cookies, $this->filterFiles($files), $server);
    }

    /**
     * Filter the given array of files, removing any empty values.
	 * 过滤给定的文件数组，删除任何空值
     *
     * @param  mixed  $files
     * @return mixed
     */
    protected function filterFiles($files)
    {
        if (! $files) {
            return;
        }

        foreach ($files as $key => $file) {
            if (is_array($file)) {
                $files[$key] = $this->filterFiles($files[$key]);
            }

            if (empty($files[$key])) {
                unset($files[$key]);
            }
        }

        return $files;
    }

    /**
     * Get the session associated with the request.
	 * 得到请求关联的会话
     *
     * @return \Illuminate\Session\Store
     *
     * @throws \RuntimeException
     */
    public function session()
    {
        if (! $this->hasSession()) {
            throw new RuntimeException('Session store not set on request.');
        }

        return $this->session;
    }

    /**
     * Get the session associated with the request.
	 * 得到请求关联的会话
     *
     * @return \Illuminate\Session\Store|null
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Set the session instance on the request.
	 * 设置请求会话实例
     *
     * @param  \Illuminate\Contracts\Session\Session  $session
     * @return void
     */
    public function setLaravelSession($session)
    {
        $this->session = $session;
    }

    /**
     * Get the user making the request.
	 * 得到发出请求的用户
     *
     * @param  string|null  $guard
     * @return mixed
     */
    public function user($guard = null)
    {
        return call_user_func($this->getUserResolver(), $guard);
    }

    /**
     * Get the route handling the request.
	 * 得到处理请求的路由
     *
     * @param  string|null  $param
     * @param  mixed  $default
     * @return \Illuminate\Routing\Route|object|string|null
     */
    public function route($param = null, $default = null)
    {
        $route = call_user_func($this->getRouteResolver());

        if (is_null($route) || is_null($param)) {
            return $route;
        }

        return $route->parameter($param, $default);
    }

    /**
     * Get a unique fingerprint for the request / route / IP address.
	 * 得到请求/路由/ IP地址的唯一指纹
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    public function fingerprint()
    {
        if (! $route = $this->route()) {
            throw new RuntimeException('Unable to generate fingerprint. Route unavailable.');
        }

        return sha1(implode('|', array_merge(
            $route->methods(),
            [$route->getDomain(), $route->uri(), $this->ip()]
        )));
    }

    /**
     * Set the JSON payload for the request.
	 * 设置JSON有效负载为请求
     *
     * @param  \Symfony\Component\HttpFoundation\ParameterBag  $json
     * @return $this
     */
    public function setJson($json)
    {
        $this->json = $json;

        return $this;
    }

    /**
     * Get the user resolver callback.
	 * 得到用户解析回调
     *
     * @return \Closure
     */
    public function getUserResolver()
    {
        return $this->userResolver ?: function () {
            //
        };
    }

    /**
     * Set the user resolver callback.
	 * 设置用户解析器回调
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function setUserResolver(Closure $callback)
    {
        $this->userResolver = $callback;

        return $this;
    }

    /**
     * Get the route resolver callback.
	 * 得到路由解析器回调
     *
     * @return \Closure
     */
    public function getRouteResolver()
    {
        return $this->routeResolver ?: function () {
            //
        };
    }

    /**
     * Set the route resolver callback.
	 * 设置路由解析器回调
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function setRouteResolver(Closure $callback)
    {
        $this->routeResolver = $callback;

        return $this;
    }

    /**
     * Get all of the input and files for the request.
	 * 得到请求的所有输入和文件
     *
     * @return array
     */
    public function toArray()
    {
        return $this->all();
    }

    /**
     * Determine if the given offset exists.
	 * 得到确定给定的偏移量是否存在
     *
     * @param  string  $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return Arr::has(
            $this->all() + $this->route()->parameters(),
            $offset
        );
    }

    /**
     * Get the value at the given offset.
	 * 得到给定偏移量处的值
     *
     * @param  string  $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }

    /**
     * Set the value at the given offset.
	 * 设置值在给定的偏移量
     *
     * @param  string  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->getInputSource()->set($offset, $value);
    }

    /**
     * Remove the value at the given offset.
	 * 移除值在给定的偏移量
     *
     * @param  string  $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        $this->getInputSource()->remove($offset);
    }

    /**
     * Check if an input element is set on the request.
	 * 检查请求上是否设置了输入元素
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return ! is_null($this->__get($key));
    }

    /**
     * Get an input element from the request.
	 * 得到请求中输入元素
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return Arr::get($this->all(), $key, function () use ($key) {
            return $this->route($key);
        });
    }
}
