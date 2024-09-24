<?php
/**
 * 视图，管理组件
 */

namespace Illuminate\View\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use InvalidArgumentException;

trait ManagesComponents
{
    /**
     * The components being rendered.
	 * 正在呈现的组件
     *
     * @var array
     */
    protected $componentStack = [];

    /**
     * The original data passed to the component.
	 * 传递给组件的原始数据
     *
     * @var array
     */
    protected $componentData = [];

    /**
     * The slot contents for the component.
	 * 组件的槽位内容
     *
     * @var array
     */
    protected $slots = [];

    /**
     * The names of the slots being rendered.
	 * 正在呈现的插槽的名称
     *
     * @var array
     */
    protected $slotStack = [];

    /**
     * Start a component rendering process.
	 * 启动一个组件呈现过程
     *
     * @param  string  $name
     * @param  array  $data
     * @return void
     */
    public function startComponent($name, array $data = [])
    {
        if (ob_start()) {
            $this->componentStack[] = $name;

            $this->componentData[$this->currentComponent()] = $data;

            $this->slots[$this->currentComponent()] = [];
        }
    }

    /**
     * Get the first view that actually exists from the given list, and start a component.
	 * 得到实际存在的第一个视图从给定列表中，并启动一个组件。
     *
     * @param  array  $names
     * @param  array  $data
     * @return void
     */
    public function startComponentFirst(array $names, array $data = [])
    {
        $name = Arr::first($names, function ($item) {
            return $this->exists($item);
        });

        $this->startComponent($name, $data);
    }

    /**
     * Render the current component.
	 * 渲染当前组件
     *
     * @return string
     */
    public function renderComponent()
    {
        $name = array_pop($this->componentStack);

        return $this->make($name, $this->componentData($name))->render();
    }

    /**
     * Get the data for the given component.
	 * 得到给定组件的数据
     *
     * @param  string  $name
     * @return array
     */
    protected function componentData($name)
    {
        return array_merge(
            $this->componentData[count($this->componentStack)],
            ['slot' => new HtmlString(trim(ob_get_clean()))],
            $this->slots[count($this->componentStack)]
        );
    }

    /**
     * Start the slot rendering process.
	 * 启动槽呈现过程
     *
     * @param  string  $name
     * @param  string|null  $content
     * @return void
     */
    public function slot($name, $content = null)
    {
        if (func_num_args() > 2) {
            throw new InvalidArgumentException('You passed too many arguments to the ['.$name.'] slot.');
        } elseif (func_num_args() === 2) {
            $this->slots[$this->currentComponent()][$name] = $content;
        } elseif (ob_start()) {
            $this->slots[$this->currentComponent()][$name] = '';

            $this->slotStack[$this->currentComponent()][] = $name;
        }
    }

    /**
     * Save the slot content for rendering.
	 * 保存槽内容以供呈现
     *
     * @return void
     */
    public function endSlot()
    {
        last($this->componentStack);

        $currentSlot = array_pop(
            $this->slotStack[$this->currentComponent()]
        );

        $this->slots[$this->currentComponent()]
                    [$currentSlot] = new HtmlString(trim(ob_get_clean()));
    }

    /**
     * Get the index for the current component.
	 * 得到当前组件的索引
     *
     * @return int
     */
    protected function currentComponent()
    {
        return count($this->componentStack) - 1;
    }
}
