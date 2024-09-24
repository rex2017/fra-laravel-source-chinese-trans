<?php
/**
 * Http，资源收集
 */

namespace Illuminate\Http\Resources\Json;

use Countable;
use Illuminate\Http\Resources\CollectsResources;
use Illuminate\Pagination\AbstractPaginator;
use IteratorAggregate;

class ResourceCollection extends JsonResource implements Countable, IteratorAggregate
{
    use CollectsResources;

    /**
     * The resource that this resource collects.
	 * 收集资源的资源
     *
     * @var string
     */
    public $collects;

    /**
     * The mapped collection instance.
	 * 映射的集合实例
     *
     * @var \Illuminate\Support\Collection
     */
    public $collection;

    /**
     * Indicates if all existing request query parameters should be added to pagination links.
	 * 指明是否应将所有现有请求查询参数添加到分页链接中
     *
     * @var bool
     */
    protected $preserveAllQueryParameters = false;

    /**
     * The query parameters that should be added to the pagination links.
	 * 应该添加到分页链接中的查询参数
     *
     * @var array|null
     */
    protected $queryParameters;

    /**
     * Create a new resource instance.
	 * 创建新的资源实例
     *
     * @param  mixed  $resource
     * @return void
     */
    public function __construct($resource)
    {
        parent::__construct($resource);

        $this->resource = $this->collectResource($resource);
    }

    /**
     * Indicate that all current query parameters should be appended to pagination links.
	 * 指明应将所有当前查询参数附加到分页链接
     *
     * @return $this
     */
    public function preserveQuery()
    {
        $this->preserveAllQueryParameters = true;

        return $this;
    }

    /**
     * Specify the query string parameters that should be present on pagination links.
	 * 指明应该出现在分页链接上的查询字符串参数
     *
     * @param  array  $query
     * @return $this
     */
    public function withQuery(array $query)
    {
        $this->preserveAllQueryParameters = false;

        $this->queryParameters = $query;

        return $this;
    }

    /**
     * Return the count of items in the resource collection.
	 * 返回资源集合中项目的计数
     *
     * @return int
     */
    public function count()
    {
        return $this->collection->count();
    }

    /**
     * Transform the resource into a JSON array.
	 * 转换资源为JSON数组
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->collection->map->toArray($request)->all();
    }

    /**
     * Create an HTTP response that represents the object.
	 * 创建表示对象的HTTP响应
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toResponse($request)
    {
        if ($this->resource instanceof AbstractPaginator) {
            return $this->preparePaginatedResponse($request);
        }

        return parent::toResponse($request);
    }

    /**
     * Create a paginate-aware HTTP response.
	 * 创建分页感知的HTTP响应
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    protected function preparePaginatedResponse($request)
    {
        if ($this->preserveAllQueryParameters) {
            $this->resource->appends($request->query());
        } elseif (! is_null($this->queryParameters)) {
            $this->resource->appends($this->queryParameters);
        }

        return (new PaginatedResourceResponse($this))->toResponse($request);
    }
}
