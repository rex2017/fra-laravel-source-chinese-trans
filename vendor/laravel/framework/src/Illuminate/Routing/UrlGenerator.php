<?php
/**
 * 路由URL生成器
 */

namespace Illuminate\Routing;

use Closure;
use Illuminate\Contracts\Routing\UrlGenerator as UrlGeneratorContract;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\InteractsWithTime;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use InvalidArgumentException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

class UrlGenerator implements UrlGeneratorContract
{
    use InteractsWithTime, Macroable;

    /**
     * The route collection.
	 * 路由收集
     *
     * @var \Illuminate\Routing\RouteCollection
     */
    protected $routes;

    /**
     * The request instance.
	 * 请求实例
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * The asset root URL.
	 * 资产根URL
     *
     * @var string
     */
    protected $assetRoot;

    /**
     * The forced URL root.
	 * 强制URL根
     *
     * @var string
     */
    protected $forcedRoot;

    /**
     * The forced scheme for URLs.
	 * URL强制方案
     *
     * @var string
     */
    protected $forceScheme;

    /**
     * A cached copy of the URL root for the current request.
	 * 当前请求的URL根的缓存副本
     *
     * @var string|null
     */
    protected $cachedRoot;

    /**
     * A cached copy of the URL scheme for the current request.
	 * 当前请求的URL方案的缓存副本
     *
     * @var string|null
     */
    protected $cachedScheme;

    /**
     * The root namespace being applied to controller actions.
	 * 应用于控制器动作的根命名空间
     *
     * @var string
     */
    protected $rootNamespace;

    /**
     * The session resolver callable.
	 * 会话解析器回调
     *
     * @var callable
     */
    protected $sessionResolver;

    /**
     * The encryption key resolver callable.
	 * 可调用的加密解析器
     *
     * @var callable
     */
    protected $keyResolver;

    /**
     * The callback to use to format hosts.
	 * 用于格式化主机的回调
     *
     * @var \Closure
     */
    protected $formatHostUsing;

    /**
     * The callback to use to format paths.
	 * 用于格式化路径的回调
     *
     * @var \Closure
     */
    protected $formatPathUsing;

    /**
     * The route URL generator instance.
	 * 路由URL生成器实例
     *
     * @var \Illuminate\Routing\RouteUrlGenerator|null
     */
    protected $routeGenerator;

    /**
     * Create a new URL Generator instance.
	 * 创建新的URL生成器实例
     *
     * @param  \Illuminate\Routing\RouteCollection  $routes
     * @param  \Illuminate\Http\Request  $request
     * @param  string|null  $assetRoot
     * @return void
     */
    public function __construct(RouteCollection $routes, Request $request, $assetRoot = null)
    {
        $this->routes = $routes;
        $this->assetRoot = $assetRoot;

        $this->setRequest($request);
    }

    /**
     * Get the full URL for the current request.
	 * 得到当前请求的完整URL
     *
     * @return string
     */
    public function full()
    {
        return $this->request->fullUrl();
    }

    /**
     * Get the current URL for the request.
	 * 得到当前URL请求
     *
     * @return string
     */
    public function current()
    {
        return $this->to($this->request->getPathInfo());
    }

    /**
     * Get the URL for the previous request.
	 * 得到前一个请求的URL
     *
     * @param  mixed  $fallback
     * @return string
     */
    public function previous($fallback = false)
    {
        $referrer = $this->request->headers->get('referer');

        $url = $referrer ? $this->to($referrer) : $this->getPreviousUrlFromSession();

        if ($url) {
            return $url;
        } elseif ($fallback) {
            return $this->to($fallback);
        }

        return $this->to('/');
    }

    /**
     * Get the previous URL from the session if possible.
	 * 从会话中得到前一个URL，如果可能的话。
     *
     * @return string|null
     */
    protected function getPreviousUrlFromSession()
    {
        $session = $this->getSession();

        return $session ? $session->previousUrl() : null;
    }

    /**
     * Generate an absolute URL to the given path.
	 * 生成给定路径的绝对URL
     *
     * @param  string  $path
     * @param  mixed  $extra
     * @param  bool|null  $secure
     * @return string
     */
    public function to($path, $extra = [], $secure = null)
    {
        // First we will check if the URL is already a valid URL. If it is we will not
        // try to generate a new one but will simply return the URL as is, which is
        // convenient since developers do not always have to check if it's valid.
		// 首先，我们将检查URL是否已经是有效的URL。
		// 如果是，我们将不会尝试生成新的URL，而只会按原样返回URL，这很方便，因为开发人员不必总是检查它是否有效。
        if ($this->isValidUrl($path)) {
            return $path;
        }

        $tail = implode('/', array_map(
            'rawurlencode', (array) $this->formatParameters($extra))
        );

        // Once we have the scheme we will compile the "tail" by collapsing the values
        // into a single string delimited by slashes. This just makes it convenient
        // for passing the array of parameters to this URL as a list of segments.
		// 一旦我们有了方案，我们将通过将值折叠成一个由斜线分隔的字符串来编译"尾部"。
		// 这只是为了方便将参数数组作为段列表传递给此URL。
        $root = $this->formatRoot($this->formatScheme($secure));

        [$path, $query] = $this->extractQueryString($path);

        return $this->format(
            $root, '/'.trim($path.'/'.$tail, '/')
        ).$query;
    }

    /**
     * Generate a secure, absolute URL to the given path.
	 * 生成给定路径的安全的绝对URL
     *
     * @param  string  $path
     * @param  array  $parameters
     * @return string
     */
    public function secure($path, $parameters = [])
    {
        return $this->to($path, $parameters, true);
    }

    /**
     * Generate the URL to an application asset.
	 * 生成应用程序资产的URL
     *
     * @param  string  $path
     * @param  bool|null  $secure
     * @return string
     */
    public function asset($path, $secure = null)
    {
        if ($this->isValidUrl($path)) {
            return $path;
        }

        // Once we get the root URL, we will check to see if it contains an index.php
        // file in the paths. If it does, we will remove it since it is not needed
        // for asset paths, but only for routes to endpoints in the application.
		// 获取根URL后，我们将检查路径中是否包含index.php文件。
		// 如果是这样，我们将删除它，因为它不需要用于资产路径，而只需要用于到应用程序中端点的路由。
        $root = $this->assetRoot
                    ? $this->assetRoot
                    : $this->formatRoot($this->formatScheme($secure));

        return $this->removeIndex($root).'/'.trim($path, '/');
    }

    /**
     * Generate the URL to a secure asset.
	 * 生成安全资产的URL
     *
     * @param  string  $path
     * @return string
     */
    public function secureAsset($path)
    {
        return $this->asset($path, true);
    }

    /**
     * Generate the URL to an asset from a custom root domain such as CDN, etc.
	 * 从自定义根域(如CDN等)生成到资产的URL
     *
     * @param  string  $root
     * @param  string  $path
     * @param  bool|null  $secure
     * @return string
     */
    public function assetFrom($root, $path, $secure = null)
    {
        // Once we get the root URL, we will check to see if it contains an index.php
        // file in the paths. If it does, we will remove it since it is not needed
        // for asset paths, but only for routes to endpoints in the application.
		// 一旦获取根URL后，我们将检查路径中是否包含index.php文件。
		// 如果是这样，我们将删除它，因为它不需要用于资产路径，而只需要用于到应用程序中端点的路由。
        $root = $this->formatRoot($this->formatScheme($secure), $root);

        return $this->removeIndex($root).'/'.trim($path, '/');
    }

    /**
     * Remove the index.php file from a path.
	 * 删除index.php文件从路径中
     *
     * @param  string  $root
     * @return string
     */
    protected function removeIndex($root)
    {
        $i = 'index.php';

        return Str::contains($root, $i) ? str_replace('/'.$i, '', $root) : $root;
    }

    /**
     * Get the default scheme for a raw URL.
	 * 得到原始URL的默认模式
     *
     * @param  bool|null  $secure
     * @return string
     */
    public function formatScheme($secure = null)
    {
        if (! is_null($secure)) {
            return $secure ? 'https://' : 'http://';
        }

        if (is_null($this->cachedScheme)) {
            $this->cachedScheme = $this->forceScheme ?: $this->request->getScheme().'://';
        }

        return $this->cachedScheme;
    }

    /**
     * Create a signed route URL for a named route.
	 * 创建签名路由URL为命名路由
     *
     * @param  string  $name
     * @param  mixed  $parameters
     * @param  \DateTimeInterface|\DateInterval|int|null  $expiration
     * @param  bool  $absolute
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function signedRoute($name, $parameters = [], $expiration = null, $absolute = true)
    {
        $this->ensureSignedRouteParametersAreNotReserved(
            $parameters = $this->formatParameters($parameters)
        );

        if ($expiration) {
            $parameters = $parameters + ['expires' => $this->availableAt($expiration)];
        }

        ksort($parameters);

        $key = call_user_func($this->keyResolver);

        return $this->route($name, $parameters + [
            'signature' => hash_hmac('sha256', $this->route($name, $parameters, $absolute), $key),
        ], $absolute);
    }

    /**
     * Ensure the given signed route parameters are not reserved.
	 * 确保给定的签名路由参数不被保留
     *
     * @param  mixed  $parameters
     * @return void
     */
    protected function ensureSignedRouteParametersAreNotReserved($parameters)
    {
        if (array_key_exists('signature', $parameters)) {
            throw new InvalidArgumentException(
                '"Signature" is a reserved parameter when generating signed routes. Please rename your route parameter.'
            );
        }

        if (array_key_exists('expires', $parameters)) {
            throw new InvalidArgumentException(
                '"Expires" is a reserved parameter when generating signed routes. Please rename your route parameter.'
            );
        }
    }

    /**
     * Create a temporary signed route URL for a named route.
	 * 创建临时签名路由URL为路由
     *
     * @param  string  $name
     * @param  \DateTimeInterface|\DateInterval|int  $expiration
     * @param  array  $parameters
     * @param  bool  $absolute
     * @return string
     */
    public function temporarySignedRoute($name, $expiration, $parameters = [], $absolute = true)
    {
        return $this->signedRoute($name, $parameters, $expiration, $absolute);
    }

    /**
     * Determine if the given request has a valid signature.
	 * 确定给定请求是否具有有效签名
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  bool  $absolute
     * @return bool
     */
    public function hasValidSignature(Request $request, $absolute = true)
    {
        return $this->hasCorrectSignature($request, $absolute)
            && $this->signatureHasNotExpired($request);
    }

    /**
     * Determine if the signature from the given request matches the URL.
	 * 确定来自给定请求的签名是否与URL匹配
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  bool  $absolute
     * @return bool
     */
    public function hasCorrectSignature(Request $request, $absolute = true)
    {
        $url = $absolute ? $request->url() : '/'.$request->path();

        $original = rtrim($url.'?'.Arr::query(
            Arr::except($request->query(), 'signature')
        ), '?');

        $signature = hash_hmac('sha256', $original, call_user_func($this->keyResolver));

        return hash_equals($signature, (string) $request->query('signature', ''));
    }

    /**
     * Determine if the expires timestamp from the given request is not from the past.
	 * 确定给定请求的过期时间戳是否不是过去的
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function signatureHasNotExpired(Request $request)
    {
        $expires = $request->query('expires');

        return ! ($expires && Carbon::now()->getTimestamp() > $expires);
    }

    /**
     * Get the URL to a named route.
	 * 得到一个命名路由的URL
     *
     * @param  string  $name
     * @param  mixed  $parameters
     * @param  bool  $absolute
     * @return string
     *
     * @throws \Symfony\Component\Routing\Exception\RouteNotFoundException
     */
    public function route($name, $parameters = [], $absolute = true)
    {
        if (! is_null($route = $this->routes->getByName($name))) {
            return $this->toRoute($route, $parameters, $absolute);
        }

        throw new RouteNotFoundException("Route [{$name}] not defined.");
    }

    /**
     * Get the URL for a given route instance.
	 * 得到给定路由实例的URL
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  mixed  $parameters
     * @param  bool  $absolute
     * @return string
     *
     * @throws \Illuminate\Routing\Exceptions\UrlGenerationException
     */
    public function toRoute($route, $parameters, $absolute)
    {
        return $this->routeUrl()->to(
            $route, $this->formatParameters($parameters), $absolute
        );
    }

    /**
     * Get the URL to a controller action.
	 * 得到一个控制器动作的URL
     *
     * @param  string|array  $action
     * @param  mixed  $parameters
     * @param  bool  $absolute
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function action($action, $parameters = [], $absolute = true)
    {
        if (is_null($route = $this->routes->getByAction($action = $this->formatAction($action)))) {
            throw new InvalidArgumentException("Action {$action} not defined.");
        }

        return $this->toRoute($route, $parameters, $absolute);
    }

    /**
     * Format the given controller action.
	 * 格式化给定的控制器动作
     *
     * @param  string|array  $action
     * @return string
     */
    protected function formatAction($action)
    {
        if (is_array($action)) {
            $action = '\\'.implode('@', $action);
        }

        if ($this->rootNamespace && strpos($action, '\\') !== 0) {
            return $this->rootNamespace.'\\'.$action;
        } else {
            return trim($action, '\\');
        }
    }

    /**
     * Format the array of URL parameters.
	 * 格式化URL参数数组
     *
     * @param  mixed|array  $parameters
     * @return array
     */
    public function formatParameters($parameters)
    {
        $parameters = Arr::wrap($parameters);

        foreach ($parameters as $key => $parameter) {
            if ($parameter instanceof UrlRoutable) {
                $parameters[$key] = $parameter->getRouteKey();
            }
        }

        return $parameters;
    }

    /**
     * Extract the query string from the given path.
	 * 提取查询字符串从给定的路径中
     *
     * @param  string  $path
     * @return array
     */
    protected function extractQueryString($path)
    {
        if (($queryPosition = strpos($path, '?')) !== false) {
            return [
                substr($path, 0, $queryPosition),
                substr($path, $queryPosition),
            ];
        }

        return [$path, ''];
    }

    /**
     * Get the base URL for the request.
	 * 得到请求的基本URL
     *
     * @param  string  $scheme
     * @param  string|null  $root
     * @return string
     */
    public function formatRoot($scheme, $root = null)
    {
        if (is_null($root)) {
            if (is_null($this->cachedRoot)) {
                $this->cachedRoot = $this->forcedRoot ?: $this->request->root();
            }

            $root = $this->cachedRoot;
        }

        $start = Str::startsWith($root, 'http://') ? 'http://' : 'https://';

        return preg_replace('~'.$start.'~', $scheme, $root, 1);
    }

    /**
     * Format the given URL segments into a single URL.
	 * 将给定的URL段格式化为单个URL
     *
     * @param  string  $root
     * @param  string  $path
     * @param  \Illuminate\Routing\Route|null  $route
     * @return string
     */
    public function format($root, $path, $route = null)
    {
        $path = '/'.trim($path, '/');

        if ($this->formatHostUsing) {
            $root = call_user_func($this->formatHostUsing, $root, $route);
        }

        if ($this->formatPathUsing) {
            $path = call_user_func($this->formatPathUsing, $path, $route);
        }

        return trim($root.$path, '/');
    }

    /**
     * Determine if the given path is a valid URL.
	 * 确定给定的路径是否是有效的URL
     *
     * @param  string  $path
     * @return bool
     */
    public function isValidUrl($path)
    {
        if (! preg_match('~^(#|//|https?://|(mailto|tel|sms):)~', $path)) {
            return filter_var($path, FILTER_VALIDATE_URL) !== false;
        }

        return true;
    }

    /**
     * Get the Route URL generator instance.
	 * 得到路由URL生成器实例
     *
     * @return \Illuminate\Routing\RouteUrlGenerator
     */
    protected function routeUrl()
    {
        if (! $this->routeGenerator) {
            $this->routeGenerator = new RouteUrlGenerator($this, $this->request);
        }

        return $this->routeGenerator;
    }

    /**
     * Set the default named parameters used by the URL generator.
	 * 设置URL生成器使用的默认命名参数
     *
     * @param  array  $defaults
     * @return void
     */
    public function defaults(array $defaults)
    {
        $this->routeUrl()->defaults($defaults);
    }

    /**
     * Get the default named parameters used by the URL generator.
	 * 得到URL生成器使用的默认命名参数
     *
     * @return array
     */
    public function getDefaultParameters()
    {
        return $this->routeUrl()->defaultParameters;
    }

    /**
     * Force the scheme for URLs.
	 * 强制URL方案
     *
     * @param  string  $scheme
     * @return void
     */
    public function forceScheme($scheme)
    {
        $this->cachedScheme = null;

        $this->forceScheme = $scheme.'://';
    }

    /**
     * Set the forced root URL.
	 * 设置强制根URL
     *
     * @param  string  $root
     * @return void
     */
    public function forceRootUrl($root)
    {
        $this->forcedRoot = rtrim($root, '/');

        $this->cachedRoot = null;
    }

    /**
     * Set a callback to be used to format the host of generated URLs.
	 * 设置一个回调，用于格式化生成的url的主机。
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function formatHostUsing(Closure $callback)
    {
        $this->formatHostUsing = $callback;

        return $this;
    }

    /**
     * Set a callback to be used to format the path of generated URLs.
	 * 设置一个回调，用于格式化生成的url的路径。
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function formatPathUsing(Closure $callback)
    {
        $this->formatPathUsing = $callback;

        return $this;
    }

    /**
     * Get the path formatter being used by the URL generator.
	 * 得到URL生成器正在使用的路径格式化程序
     *
     * @return \Closure
     */
    public function pathFormatter()
    {
        return $this->formatPathUsing ?: function ($path) {
            return $path;
        };
    }

    /**
     * Get the request instance.
	 * 得到请求实例
     *
     * @return \Illuminate\Http\Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Set the current request instance.
	 * 设置当前请求实例 
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        $this->cachedRoot = null;
        $this->cachedScheme = null;
        $this->routeGenerator = null;
    }

    /**
     * Set the route collection.
	 * 设置路由集合
     *
     * @param  \Illuminate\Routing\RouteCollection  $routes
     * @return $this
     */
    public function setRoutes(RouteCollection $routes)
    {
        $this->routes = $routes;

        return $this;
    }

    /**
     * Get the session implementation from the resolver.
	 * 得到会话实现从解析器
     *
     * @return \Illuminate\Session\Store|null
     */
    protected function getSession()
    {
        if ($this->sessionResolver) {
            return call_user_func($this->sessionResolver);
        }
    }

    /**
     * Set the session resolver for the generator.
	 * 设置会话解析器为生成器
     *
     * @param  callable  $sessionResolver
     * @return $this
     */
    public function setSessionResolver(callable $sessionResolver)
    {
        $this->sessionResolver = $sessionResolver;

        return $this;
    }

    /**
     * Set the encryption key resolver.
	 * 设置加密密钥解析器
     *
     * @param  callable  $keyResolver
     * @return $this
     */
    public function setKeyResolver(callable $keyResolver)
    {
        $this->keyResolver = $keyResolver;

        return $this;
    }

    /**
     * Set the root controller namespace.
	 * 设置根控制器命名空间
     *
     * @param  string  $rootNamespace
     * @return $this
     */
    public function setRootControllerNamespace($rootNamespace)
    {
        $this->rootNamespace = $rootNamespace;

        return $this;
    }
}
