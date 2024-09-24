<?php
/**
 * 分页URL窗体
 */

namespace Illuminate\Pagination;

use Illuminate\Contracts\Pagination\LengthAwarePaginator as PaginatorContract;

class UrlWindow
{
    /**
     * The paginator implementation.
	 * 分页器实现
     *
     * @var \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    protected $paginator;

    /**
     * Create a new URL window instance.
	 * 创建新的URL窗体实例
     *
     * @param  \Illuminate\Contracts\Pagination\LengthAwarePaginator  $paginator
     * @return void
     */
    public function __construct(PaginatorContract $paginator)
    {
        $this->paginator = $paginator;
    }

    /**
     * Create a new URL window instance.
	 * 创建新的URL窗体实例
     *
     * @param  \Illuminate\Contracts\Pagination\LengthAwarePaginator  $paginator
     * @return array
     */
    public static function make(PaginatorContract $paginator)
    {
        return (new static($paginator))->get();
    }

    /**
     * Get the window of URLs to be shown.
	 * 得到要显示的url窗口
     *
     * @return array
     */
    public function get()
    {
        $onEachSide = $this->paginator->onEachSide;

        if ($this->paginator->lastPage() < ($onEachSide * 2) + 6) {
            return $this->getSmallSlider();
        }

        return $this->getUrlSlider($onEachSide);
    }

    /**
     * Get the slider of URLs there are not enough pages to slide.
	 * 得到URL的滑块，因为没有足够的页面可以滑动
     *
     * @return array
     */
    protected function getSmallSlider()
    {
        return [
            'first'  => $this->paginator->getUrlRange(1, $this->lastPage()),
            'slider' => null,
            'last'   => null,
        ];
    }

    /**
     * Create a URL slider links.
	 * 创建URL滑块链接
     *
     * @param  int  $onEachSide
     * @return array
     */
    protected function getUrlSlider($onEachSide)
    {
        $window = $onEachSide * 2;

        if (! $this->hasPages()) {
            return ['first' => null, 'slider' => null, 'last' => null];
        }

        // If the current page is very close to the beginning of the page range, we will
        // just render the beginning of the page range, followed by the last 2 of the
        // links in this list, since we will not have room to create a full slider.
		// 如果当前页面非常接近页面范围的开头，我们将只呈现页面范围的开始，
		// 然后是此列表中的最后2个链接，因为我们没有空间创建完整的滑块。
        if ($this->currentPage() <= $window) {
            return $this->getSliderTooCloseToBeginning($window);
        }

        // If the current page is close to the ending of the page range we will just get
        // this first couple pages, followed by a larger window of these ending pages
        // since we're too close to the end of the list to create a full on slider.
		// 如果当前页面接近页面范围的末尾，我们将只得到前几页，然后是这些结束页面的较大窗口，
		// 因为我们离列表末尾太近，无法创建完整的滑块。
        elseif ($this->currentPage() > ($this->lastPage() - $window)) {
            return $this->getSliderTooCloseToEnding($window);
        }

        // If we have enough room on both sides of the current page to build a slider we
        // will surround it with both the beginning and ending caps, with this window
        // of pages in the middle providing a Google style sliding paginator setup.
		// 如果我们在当前页面的两侧都有足够的空间来构建一个滑块，我们将用首字母和尾字母围绕它，
		// 中间的页面窗口提供谷歌风格的滑动分页器设置。
        return $this->getFullSlider($onEachSide);
    }

    /**
     * Get the slider of URLs when too close to beginning of window.
	 * 得到url的滑动条，当太接近窗口开始时。
     *
     * @param  int  $window
     * @return array
     */
    protected function getSliderTooCloseToBeginning($window)
    {
        return [
            'first' => $this->paginator->getUrlRange(1, $window + 2),
            'slider' => null,
            'last' => $this->getFinish(),
        ];
    }

    /**
     * Get the slider of URLs when too close to ending of window.
	 * 得到url的滑动条当太接近窗口结束时
     *
     * @param  int  $window
     * @return array
     */
    protected function getSliderTooCloseToEnding($window)
    {
        $last = $this->paginator->getUrlRange(
            $this->lastPage() - ($window + 2),
            $this->lastPage()
        );

        return [
            'first' => $this->getStart(),
            'slider' => null,
            'last' => $last,
        ];
    }

    /**
     * Get the slider of URLs when a full slider can be made.
	 * 得到url的滑块当一个完整的滑块可以制作时
     *
     * @param  int  $onEachSide
     * @return array
     */
    protected function getFullSlider($onEachSide)
    {
        return [
            'first'  => $this->getStart(),
            'slider' => $this->getAdjacentUrlRange($onEachSide),
            'last'   => $this->getFinish(),
        ];
    }

    /**
     * Get the page range for the current page window.
	 * 得到当前页窗口的页范围
     *
     * @param  int  $onEachSide
     * @return array
     */
    public function getAdjacentUrlRange($onEachSide)
    {
        return $this->paginator->getUrlRange(
            $this->currentPage() - $onEachSide,
            $this->currentPage() + $onEachSide
        );
    }

    /**
     * Get the starting URLs of a pagination slider.
	 * 得到分页滑块的起始URl
     *
     * @return array
     */
    public function getStart()
    {
        return $this->paginator->getUrlRange(1, 2);
    }

    /**
     * Get the ending URLs of a pagination slider.
	 * 得到分页滑块的结束URL
     *
     * @return array
     */
    public function getFinish()
    {
        return $this->paginator->getUrlRange(
            $this->lastPage() - 1,
            $this->lastPage()
        );
    }

    /**
     * Determine if the underlying paginator being presented has pages to show.
	 * 确定所显示的底层分页器是否有要显示的页面
     *
     * @return bool
     */
    public function hasPages()
    {
        return $this->paginator->lastPage() > 1;
    }

    /**
     * Get the current page from the paginator.
	 * 得到当前页从分页器
     *
     * @return int
     */
    protected function currentPage()
    {
        return $this->paginator->currentPage();
    }

    /**
     * Get the last page from the paginator.
	 * 得到最后一页从分页器
     *
     * @return int
     */
    protected function lastPage()
    {
        return $this->paginator->lastPage();
    }
}
