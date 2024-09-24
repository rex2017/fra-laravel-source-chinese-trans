<?php
/**
 * 基础，发出Http请求
 */

namespace Illuminate\Foundation\Testing\Concerns;

use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Foundation\Testing\TestResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

trait MakesHttpRequests
{
    /**
     * Additional headers for the request.
	 * 请求的附加标头
     *
     * @var array
     */
    protected $defaultHeaders = [];

    /**
     * Additional cookies for the request.
	 * 请求的附加cookie
     *
     * @var array
     */
    protected $defaultCookies = [];

    /**
     * Additional cookies will not be encrypted for the request.
	 * 其他cookie将不会为请求加密
     *
     * @var array
     */
    protected $unencryptedCookies = [];

    /**
     * Additional server variables for the request.
	 * 请求的其他服务器变量
     *
     * @var array
     */
    protected $serverVariables = [];

    /**
     * Indicates whether redirects should be followed.
	 * 指明是否应该遵循重定向
     *
     * @var bool
     */
    protected $followRedirects = false;

    /**
     * Indicates whether cookies should be encrypted.
	 * 指明是否会话将被加密
     *
     * @var bool
     */
    protected $encryptCookies = true;

    /**
     * Define additional headers to be sent with the request.
	 * 定义要随请求一起发送的附加标头
     *
     * @param  array  $headers
     * @return $this
     */
    public function withHeaders(array $headers)
    {
        $this->defaultHeaders = array_merge($this->defaultHeaders, $headers);

        return $this;
    }

    /**
     * Add a header to be sent with the request.
	 * 添加与请求一起发送的标头
     *
     * @param  string  $name
     * @param  string  $value
     * @return $this
     */
    public function withHeader(string $name, string $value)
    {
        $this->defaultHeaders[$name] = $value;

        return $this;
    }

    /**
     * Flush all the configured headers.
	 * 刷新所有配置的标头
     *
     * @return $this
     */
    public function flushHeaders()
    {
        $this->defaultHeaders = [];

        return $this;
    }

    /**
     * Define a set of server variables to be sent with the requests.
	 * 定义一组要随请求一起发送的服务器变量
     *
     * @param  array  $server
     * @return $this
     */
    public function withServerVariables(array $server)
    {
        $this->serverVariables = $server;

        return $this;
    }

    /**
     * Disable middleware for the test.
	 * 禁用测试的中间件
     *
     * @param  string|array|null  $middleware
     * @return $this
     */
    public function withoutMiddleware($middleware = null)
    {
        if (is_null($middleware)) {
            $this->app->instance('middleware.disable', true);

            return $this;
        }

        foreach ((array) $middleware as $abstract) {
            $this->app->instance($abstract, new class
            {
                public function handle($request, $next)
                {
                    return $next($request);
                }
            });
        }

        return $this;
    }

    /**
     * Enable the given middleware for the test.
	 * 为测试启用给定的中间件
     *
     * @param  string|array|null  $middleware
     * @return $this
     */
    public function withMiddleware($middleware = null)
    {
        if (is_null($middleware)) {
            unset($this->app['middleware.disable']);

            return $this;
        }

        foreach ((array) $middleware as $abstract) {
            unset($this->app[$abstract]);
        }

        return $this;
    }

    /**
     * Define additional cookies to be sent with the request.
	 * 定义要随请求一起发送的其他cookie
     *
     * @param  array  $cookies
     * @return $this
     */
    public function withCookies(array $cookies)
    {
        $this->defaultCookies = array_merge($this->defaultCookies, $cookies);

        return $this;
    }

    /**
     * Add a cookie to be sent with the request.
	 * 添加要随请求一起发送的cookie
     *
     * @param  string  $name
     * @param  string  $value
     * @return $this
     */
    public function withCookie(string $name, string $value)
    {
        $this->defaultCookies[$name] = $value;

        return $this;
    }

    /**
     * Define additional cookies will not be encrypted before sending with the request.
	 * 定义在发送请求之前不会加密的附加cookie
     *
     * @param  array  $cookies
     * @return $this
     */
    public function withUnencryptedCookies(array $cookies)
    {
        $this->unencryptedCookies = array_merge($this->unencryptedCookies, $cookies);

        return $this;
    }

    /**
     * Add a cookie will not be encrypted before sending with the request.
	 * 添加cookie在发送请求之前不会被加密
     *
     * @param  string  $name
     * @param  string  $value
     * @return $this
     */
    public function withUnencryptedCookie(string $name, string $value)
    {
        $this->unencryptedCookies[$name] = $value;

        return $this;
    }

    /**
     * Automatically follow any redirects returned from the response.
	 * 自动遵循从响应返回的任何重定向
     *
     * @return $this
     */
    public function followingRedirects()
    {
        $this->followRedirects = true;

        return $this;
    }

    /**
     * Disable automatic encryption of cookie values.
	 * 禁用cookie值的自动加密功能
     *
     * @return $this
     */
    public function disableCookieEncryption()
    {
        $this->encryptCookies = false;

        return $this;
    }

    /**
     * Set the referer header and previous URL session value in order to simulate a previous request.
	 * 设置引用头和以前的URL会话值，以模拟以前的请求。
     *
     * @param  string  $url
     * @return $this
     */
    public function from(string $url)
    {
        $this->app['session']->setPreviousUrl($url);

        return $this->withHeader('referer', $url);
    }

    /**
     * Visit the given URI with a GET request.
	 * 访问给定的URI使用GET请求
     *
     * @param  string  $uri
     * @param  array  $headers
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    public function get($uri, array $headers = [])
    {
        $server = $this->transformHeadersToServerVars($headers);
        $cookies = $this->prepareCookiesForRequest();

        return $this->call('GET', $uri, [], $cookies, [], $server);
    }

    /**
     * Visit the given URI with a GET request, expecting a JSON response.
	 * 访问给定的URI使用GET请求，期望得到JSON响应。
     *
     * @param  string  $uri
     * @param  array  $headers
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    public function getJson($uri, array $headers = [])
    {
        return $this->json('GET', $uri, [], $headers);
    }

    /**
     * Visit the given URI with a POST request.
	 * 访问给定的URI使用POST请求
     *
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    public function post($uri, array $data = [], array $headers = [])
    {
        $server = $this->transformHeadersToServerVars($headers);
        $cookies = $this->prepareCookiesForRequest();

        return $this->call('POST', $uri, $data, $cookies, [], $server);
    }

    /**
     * Visit the given URI with a POST request, expecting a JSON response.
	 * 访问给定的URI使用POST请求，期望得到JSON响应。
     *
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    public function postJson($uri, array $data = [], array $headers = [])
    {
        return $this->json('POST', $uri, $data, $headers);
    }

    /**
     * Visit the given URI with a PUT request.
	 * 访问给定的URI使用PUT请求
     *
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    public function put($uri, array $data = [], array $headers = [])
    {
        $server = $this->transformHeadersToServerVars($headers);
        $cookies = $this->prepareCookiesForRequest();

        return $this->call('PUT', $uri, $data, $cookies, [], $server);
    }

    /**
     * Visit the given URI with a PUT request, expecting a JSON response.
	 * 访问给定的URI使用PUT请求，期望得到JSON响应。
     *
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    public function putJson($uri, array $data = [], array $headers = [])
    {
        return $this->json('PUT', $uri, $data, $headers);
    }

    /**
     * Visit the given URI with a PATCH request.
	 * 访问给定的URI使用PATCH请求
     *
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    public function patch($uri, array $data = [], array $headers = [])
    {
        $server = $this->transformHeadersToServerVars($headers);
        $cookies = $this->prepareCookiesForRequest();

        return $this->call('PATCH', $uri, $data, $cookies, [], $server);
    }

    /**
     * Visit the given URI with a PATCH request, expecting a JSON response.
	 * 访问给定的URI使用PATCH请求，期望得到JSON响应。
     *
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    public function patchJson($uri, array $data = [], array $headers = [])
    {
        return $this->json('PATCH', $uri, $data, $headers);
    }

    /**
     * Visit the given URI with a DELETE request.
	 * 访问给定的URI使用DELETE请求
     *
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    public function delete($uri, array $data = [], array $headers = [])
    {
        $server = $this->transformHeadersToServerVars($headers);
        $cookies = $this->prepareCookiesForRequest();

        return $this->call('DELETE', $uri, $data, $cookies, [], $server);
    }

    /**
     * Visit the given URI with a DELETE request, expecting a JSON response.
	 * 访问给定的URI使用DELETE请求，期望得到JSON响应。
     *
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    public function deleteJson($uri, array $data = [], array $headers = [])
    {
        return $this->json('DELETE', $uri, $data, $headers);
    }

    /**
     * Visit the given URI with a OPTIONS request.
	 * 访问给定的URI使用OPTIONS请求
     *
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    public function options($uri, array $data = [], array $headers = [])
    {
        $server = $this->transformHeadersToServerVars($headers);
        $cookies = $this->prepareCookiesForRequest();

        return $this->call('OPTIONS', $uri, $data, $cookies, [], $server);
    }

    /**
     * Visit the given URI with a OPTIONS request, expecting a JSON response.
	 * 访问给定的URI使用OPTIONS请求，期望得到JSON响应。
     *
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    public function optionsJson($uri, array $data = [], array $headers = [])
    {
        return $this->json('OPTIONS', $uri, $data, $headers);
    }

    /**
     * Call the given URI with a JSON request.
	 * 调用给定的URI用JSON请求
     *
     * @param  string  $method
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    public function json($method, $uri, array $data = [], array $headers = [])
    {
        $files = $this->extractFilesFromDataArray($data);

        $content = json_encode($data);

        $headers = array_merge([
            'CONTENT_LENGTH' => mb_strlen($content, '8bit'),
            'CONTENT_TYPE' => 'application/json',
            'Accept' => 'application/json',
        ], $headers);

        return $this->call(
            $method, $uri, [], [], $files, $this->transformHeadersToServerVars($headers), $content
        );
    }

    /**
     * Call the given URI and return the Response.
	 * 调用给定的URI并返回响应
     *
     * @param  string  $method
     * @param  string  $uri
     * @param  array  $parameters
     * @param  array  $cookies
     * @param  array  $files
     * @param  array  $server
     * @param  string|null  $content
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $kernel = $this->app->make(HttpKernel::class);

        $files = array_merge($files, $this->extractFilesFromDataArray($parameters));

        $symfonyRequest = SymfonyRequest::create(
            $this->prepareUrlForRequest($uri), $method, $parameters,
            $cookies, $files, array_replace($this->serverVariables, $server), $content
        );

        $response = $kernel->handle(
            $request = Request::createFromBase($symfonyRequest)
        );

        if ($this->followRedirects) {
            $response = $this->followRedirects($response);
        }

        $kernel->terminate($request, $response);

        return $this->createTestResponse($response);
    }

    /**
     * Turn the given URI into a fully qualified URL.
	 * 转换给定的URI为完全限定的URL
     *
     * @param  string  $uri
     * @return string
     */
    protected function prepareUrlForRequest($uri)
    {
        if (Str::startsWith($uri, '/')) {
            $uri = substr($uri, 1);
        }

        return trim(url($uri), '/');
    }

    /**
     * Transform headers array to array of $_SERVER vars with HTTP_* format.
	 * 转换headers数组为HTTP_*格式的$_SERVER变量数组
     *
     * @param  array  $headers
     * @return array
     */
    protected function transformHeadersToServerVars(array $headers)
    {
        return collect(array_merge($this->defaultHeaders, $headers))->mapWithKeys(function ($value, $name) {
            $name = strtr(strtoupper($name), '-', '_');

            return [$this->formatServerHeaderKey($name) => $value];
        })->all();
    }

    /**
     * Format the header name for the server array.
	 * 格式化服务器数组的标头名称
     *
     * @param  string  $name
     * @return string
     */
    protected function formatServerHeaderKey($name)
    {
        if (! Str::startsWith($name, 'HTTP_') && $name !== 'CONTENT_TYPE' && $name !== 'REMOTE_ADDR') {
            return 'HTTP_'.$name;
        }

        return $name;
    }

    /**
     * Extract the file uploads from the given data array.
	 * 提取文件上传从给定的数据数组中
     *
     * @param  array  $data
     * @return array
     */
    protected function extractFilesFromDataArray(&$data)
    {
        $files = [];

        foreach ($data as $key => $value) {
            if ($value instanceof SymfonyUploadedFile) {
                $files[$key] = $value;

                unset($data[$key]);
            }

            if (is_array($value)) {
                $files[$key] = $this->extractFilesFromDataArray($value);

                $data[$key] = $value;
            }
        }

        return $files;
    }

    /**
     * If enabled, encrypt cookie values for request.
	 * 如果启用，为请求加密cookie值。
     *
     * @return array
     */
    protected function prepareCookiesForRequest()
    {
        if (! $this->encryptCookies) {
            return array_merge($this->defaultCookies, $this->unencryptedCookies);
        }

        return collect($this->defaultCookies)->map(function ($value, $key) {
            return encrypt(CookieValuePrefix::create($key, app('encrypter')->getKey()).$value, false);
        })->merge($this->unencryptedCookies)->all();
    }

    /**
     * Follow a redirect chain until a non-redirect is received.
	 * 遵循重定向链，直到接收到非重定向。
     *
     * @param  \Illuminate\Http\Response  $response
     * @return \Illuminate\Http\Response|\Illuminate\Foundation\Testing\TestResponse
     */
    protected function followRedirects($response)
    {
        while ($response->isRedirect()) {
            $response = $this->get($response->headers->get('Location'));
        }

        $this->followRedirects = false;

        return $response;
    }

    /**
     * Create the test response instance from the given response.
	 * 创建测试响应实例根据给定的响应
     *
     * @param  \Illuminate\Http\Response  $response
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    protected function createTestResponse($response)
    {
        return TestResponse::fromBaseResponse($response);
    }
}
