<?php
/**
 * 契约，长分页接口
 */

namespace Illuminate\Contracts\Pagination;

interface LengthAwarePaginator extends Paginator
{
    /**
     * Create a range of pagination URLs.
	 * 创建随机分页
     *
     * @param  int  $start
     * @param  int  $end
     * @return array
     */
    public function getUrlRange($start, $end);

    /**
     * Determine the total number of items in the data store.
	 * 确定数据存储中的项目总数
     *
     * @return int
     */
    public function total();

    /**
     * Get the page number of the last available page.
	 * 得到最后可用页的页码
     *
     * @return int
     */
    public function lastPage();
}
