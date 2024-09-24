<?php
/**
 * 路由URL生成器
 */

namespace Illuminate\Routing;

use Illuminate\Routing\Exceptions\UrlGenerationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class RouteUrlGenerator
{
    /**
     * The URL generator instance.
	 * URL生成器实例
     *
     * @var \Illuminate\Routing\UrlGenerator
     */
    protected $url;

    /**
     * The request instance.
	 * 请求实例
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * The named parameter defaults.
	 * 默认参数
     *
     * @var array
     */
    public $defaultParameters = [];

    /**
     * Characters that should not be URL encoded.
	 * 不应该被URL编码的字符
     *
     * @var array
     */
    public $dontEncode = [
        '%2F' => '/',
        '%40' => '@',
        '%3A' => ':',
        '%3B' => ';',
        '%2C' => ',',
        '%3D' => '=',
        '%2B' => '+',
        '%21' => '!',
        '%2A' => '*',
        '%7C' => '|',
        '%3F' => '?',
        '%26' => '&',
        '%23' => '#',
        '%25' => '%',
    ];

    /**
     * Create a new Route URL generator.
	 * 创建新的路由URL生成器
     *
     * @param  \Illuminate\Routing\UrlGenerator  $url
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function __construct($url, $request)
    {
        $this->url = $url;
        $this->request = $request;
    }

    /**
     * Generate a URL for the given route.
	 * 生成URL为给定的路由
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  array  $parameters
     * @param  bool  $absolute
     * @return string
     *
     * @throws \Illuminate\Routing\Exceptions\UrlGenerationException
     */
    public function to($route, $parameters = [], $absolute = false)
    {
        $domain = $this->getRouteDomain($route, $parameters);

        // First we will construct the entire URI including the root and query string. Once it
        // has been constructed, we'll make sure we don't have any missing parameters or we
        // will need to throw the exception to let the developers know one was not given.
		// 首先，我们将构造整个URI，包括根和查询字符串。
		// 一旦构造完成，我们将确保没有任何缺失的参数，否则我们需要抛出异常，让开发人员知道没有给出参数。
        $uri = $this->addQueryString($this->url->format(
            $root = $this->replaceRootParameters($route, $domain, $parameters),
            $this->replaceRouteParameters($route->uri(), $parameters),
            $route
        ), $parameters);

        if (preg_match('/\{.*?\}/', $uri)) {
            throw UrlGenerationException::forMissingParameters($route);
        }

        // Once we have ensured that there are no missing parameters in the URI we will encode
        // the URI and prepare it for returning to the developer. If the URI is supposed to
        // be absolute, we will return it as-is. Otherwise we will remove the URL's root.
		// 一旦我们确保URI中没有缺失的参数，我们将对URI进行编码，并准备将其返回给开发人员。
		// 如果URI应该是绝对的，我们将按原样返回。否则，我们将删除URL的根。
        $uri = strtr(rawurlencode($uri), $this->dontEncode);

        if (! $absolute) {
            $uri = preg_replace('#^(//|[^/?])+#', '', $uri);

            if ($base = $this->request->getBaseUrl()) {
                $uri = preg_replace('#^'.$base.'#i', '', $uri);
            }

            return '/'.ltrim($uri, '/');
        }

        return $uri;
    }

    /**
     * Get the formatted domain for a given route.
	 * 得到给定路由的格式化域
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  array  $parameters
     * @return string
     */
    protected function getRouteDomain($route, &$parameters)
    {
        return $route->getDomain() ? $this->formatDomain($route, $parameters) : null;
    }

    /**
     * Format the domain and port for the route and request.
	 * 格式化路由和请求的域和端口
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  array  $parameters
     * @return string
     */
    protected function formatDomain($route, &$parameters)
    {
        return $this->addPortToDomain(
            $this->getRouteScheme($route).$route->getDomain()
        );
    }

    /**
     * Get the scheme for the given route.
	 * 得到给定路线的方案
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return string
     */
    protected function getRouteScheme($route)
    {
        if ($route->httpOnly()) {
            return 'http://';
        } elseif ($route->httpsOnly()) {
            return 'https://';
        }

        return $this->url->formatScheme();
    }

    /**
     * Add the port to the domain if necessary.
	 *将端口加入域根据需要
     *
     * @param  string  $domain
     * @return string
     */
    protected function addPortToDomain($domain)
    {
        $secure = $this->request->isSecure();

        $port = (int) $this->request->getPort();

        return ($secure && $port === 443) || (! $secure && $port === 80)
                    ? $domain : $domain.':'.$port;
    }

    /**
     * Replace the parameters on the root path.
	 * 替换根路径上的参数
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  string  $domain
     * @param  array  $parameters
     * @return string
     */
    protected function replaceRootParameters($route, $domain, &$parameters)
    {
        $scheme = $this->getRouteScheme($route);

        return $this->replaceRouteParameters(
            $this->url->formatRoot($scheme, $domain), $parameters
        );
    }

    /**
     * Replace all of the wildcard parameters for a route path.
	 * 替换路由路径的所有通配符参数
     *
     * @param  string  $path
     * @param  array  $parameters
     * @return string
     */
    protected function replaceRouteParameters($path, array &$parameters)
    {
        $path = $this->replaceNamedParameters($path, $parameters);

        $path = preg_replace_callback('/\{.*?\}/', function ($match) use (&$parameters) {
            // Reset only the numeric keys...
            $parameters = array_merge($parameters);

            return (! isset($parameters[0]) && ! Str::endsWith($match[0], '?}'))
                        ? $match[0]
                        : Arr::pull($parameters, 0);
        }, $path);

        return trim(preg_replace('/\{.*?\?\}/', '', $path), '/');
    }

    /**
     * Replace all of the named parameters in the path.
	 * 替换路径中的所有命名参数
     *
     * @param  string  $path
     * @param  array  $parameters
     * @return string
     */
    protected function replaceNamedParameters($path, &$parameters)
    {
        return preg_replace_callback('/\{(.*?)(\?)?\}/', function ($m) use (&$parameters) {
            if (isset($parameters[$m[1]]) && $parameters[$m[1]] !== '') {
                return Arr::pull($parameters, $m[1]);
            } elseif (isset($this->defaultParameters[$m[1]])) {
                return $this->defaultParameters[$m[1]];
            } elseif (isset($parameters[$m[1]])) {
                Arr::pull($parameters, $m[1]);
            }

            return $m[0];
        }, $path);
    }

    /**
     * Add a query string to the URI.
	 * 向URI添加查询字符串
     *
     * @param  string  $uri
     * @param  array  $parameters
     * @return mixed|string
     */
    protected function addQueryString($uri, array $parameters)
    {
        // If the URI has a fragment we will move it to the end of this URI since it will
        // need to come after any query string that may be added to the URL else it is
        // not going to be available. We will remove it then append it back on here.
		// 如果URI有一个片段，我们会将其移动到此URI的末尾，因为它需要在可能添加到URL的任何查询字符串之后，
		// 否则它将不可用。我们将删除它，然后将其重新附加到此处。
        if (! is_null($fragment = parse_url($uri, PHP_URL_FRAGMENT))) {
            $uri = preg_replace('/#.*/', '', $uri);
        }

        $uri .= $this->getRouteQueryString($parameters);

        return is_null($fragment) ? $uri : $uri."#{$fragment}";
    }

    /**
     * Get the query string for a given route.
	 * 得到路由查询字符串
     *
     * @param  array  $parameters
     * @return string
     */
    protected function getRouteQueryString(array $parameters)
    {
        // First we will get all of the string parameters that are remaining after we
        // have replaced the route wildcards. We'll then build a query string from
        // these string parameters then use it as a starting point for the rest.
		// 首先，我们将获取替换路由通配符后剩余的所有字符串参数。
		// 然后，我们将根据这些字符串参数构建一个查询字符串，然后将其用作其余操作的起点。
        if (count($parameters) === 0) {
            return '';
        }

        $query = Arr::query(
            $keyed = $this->getStringParameters($parameters)
        );

        // Lastly, if there are still parameters remaining, we will fetch the numeric
        // parameters that are in the array and add them to the query string or we
        // will make the initial query string if it wasn't started with strings.
		// 最后，如果还有剩余的参数，我们将获取数组中的数字参数并将其添加到查询字符串中，
		// 或者如果初始查询字符串不是以字符串开头的，我们将生成初始查询字符串。
        if (count($keyed) < count($parameters)) {
            $query .= '&'.implode(
                '&', $this->getNumericParameters($parameters)
            );
        }

        $query = trim($query, '&');

        return $query === '' ? '' : "?{$query}";
    }

    /**
     * Get the string parameters from a given list.
	 * 得到字符串参数从给定列表中
     *
     * @param  array  $parameters
     * @return array
     */
    protected function getStringParameters(array $parameters)
    {
        return array_filter($parameters, 'is_string', ARRAY_FILTER_USE_KEY);
    }

    /**
     * Get the numeric parameters from a given list.
	 * 得到数值参数从给定列表中
     *
     * @param  array  $parameters
     * @return array
     */
    protected function getNumericParameters(array $parameters)
    {
        return array_filter($parameters, 'is_numeric', ARRAY_FILTER_USE_KEY);
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
        $this->defaultParameters = array_merge(
            $this->defaultParameters, $defaults
        );
    }
}
