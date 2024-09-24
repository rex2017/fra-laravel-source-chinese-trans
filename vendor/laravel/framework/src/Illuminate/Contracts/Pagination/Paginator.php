<?php
/**
 * 契约，分页接口
 */

namespace Illuminate\Contracts\Pagination;

interface Paginator
{
    /**
     * Get the URL for a given page.
	 * 得到给定页面的URL
     *
     * @param  int  $page
     * @return string
     */
    public function url($page);

    /**
     * Add a set of query string values to the paginator.
	 * 添加查询字符串值至分页器
     *
     * @param  array|string  $key
     * @param  string|null  $value
     * @return $this
     */
    public function appends($key, $value = null);

    /**
     * Get / set the URL fragment to be appended to URLs.
	 * 获取/设置要附加到URL的URL片段
     *
     * @param  string|null  $fragment
     * @return $this|string
     */
    public function fragment($fragment = null);

    /**
     * The URL for the next page, or null.
	 × 下一页URL，或者为空
     *
     * @return string|null
     */
    public function nextPageUrl();

    /**
     * Get the URL for the previous page, or null.
	 * 得到前一页的URL，或者为空
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
	 * 得到第一个被分页项的"索引"
     *
     * @return int
     */
    public function firstItem();

    /**
     * Get the "index" of the last item being paginated.
	 * 得到最后一个被分页项的"索引"
     *
     * @return int
     */
    public function lastItem();

    /**
     * Determine how many items are being shown per page.
	 * 确定每页显示多少项
     *
     * @return int
     */
    public function perPage();

    /**
     * Determine the current page being paginated.
	 * 确定正在分页的当前页面
     *
     * @return int
     */
    public function currentPage();

    /**
     * Determine if there are enough items to split into multiple pages.
	 * 确定是否有足够的项目可以拆分为多个页面
     *
     * @return bool
     */
    public function hasPages();

    /**
     * Determine if there are more items in the data store.
	 * 确定数据存储中是否有更多项
     *
     * @return bool
     */
    public function hasMorePages();

    /**
     * Get the base path for paginator generated URLs.
	 * 得到分页器生成的url的基本路径
     *
     * @return string|null
     */
    public function path();

    /**
     * Determine if the list of items is empty or not.
	 * 确定项目列表是否为空
     *
     * @return bool
     */
    public function isEmpty();

    /**
     * Determine if the list of items is not empty.
	 * 确定项目列表是否不为空
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
