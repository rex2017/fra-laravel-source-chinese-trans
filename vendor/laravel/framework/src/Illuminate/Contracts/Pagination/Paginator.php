<?php
/**
 * 契约，分页接口
 */

namespace Illuminate\Contracts\Pagination;

interface Paginator
{
    /**
     * Get the URL for a given page.
	 * 得到URL
     *
     * @param  int  $page
     * @return string
     */
    public function url($page);

    /**
     * Add a set of query string values to the paginator.
	 * 添加查询字符串值向分页器
     *
     * @param  array|string  $key
     * @param  string|null  $value
     * @return $this
     */
    public function appends($key, $value = null);

    /**
     * Get / set the URL fragment to be appended to URLs.
     *
     * @param  string|null  $fragment
     * @return $this|string
     */
    public function fragment($fragment = null);

    /**
     * The URL for the next page, or null.
	 × 下一页URL
     *
     * @return string|null
     */
    public function nextPageUrl();

    /**
     * Get the URL for the previous page, or null.
     *
     * @return string|null
     */
    public function previousPageUrl();

    /**
     * Get all of the items being paginated.
	 * 得到所有被分页的项
     *
     * @return array
     */
    public function items();

    /**
     * Get the "index" of the first item being paginated.
     *
     * @return int
     */
    public function firstItem();

    /**
     * Get the "index" of the last item being paginated.
     *
     * @return int
     */
    public function lastItem();

    /**
     * Determine how many items are being shown per page.
     *
     * @return int
     */
    public function perPage();

    /**
     * Determine the current page being paginated.
     *
     * @return int
     */
    public function currentPage();

    /**
     * Determine if there are enough items to split into multiple pages.
     *
     * @return bool
     */
    public function hasPages();

    /**
     * Determine if there are more items in the data store.
     *
     * @return bool
     */
    public function hasMorePages();

    /**
     * Get the base path for paginator generated URLs.
     *
     * @return string|null
     */
    public function path();

    /**
     * Determine if the list of items is empty or not.
	 * 确定是否列表为空
     *
     * @return bool
     */
    public function isEmpty();

    /**
     * Determine if the list of items is not empty.
	 * 确定是否列表不为空
     *
     * @return bool
     */
    public function isNotEmpty();

    /**
     * Render the paginator using a given view.
	 * 呈现分页器使用视图
     *
     * @param  string|null  $view
     * @param  array  $data
     * @return string
     */
    public function render($view = null, $data = []);
}
