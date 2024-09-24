<?php
/**
 * Http，合并值
 */

namespace Illuminate\Http\Resources;

use Illuminate\Support\Collection;
use JsonSerializable;

class MergeValue
{
    /**
     * The data to be merged.
	 * 待合并的数据
     *
     * @var array
     */
    public $data;

    /**
     * Create new merge value instance.
	 * 创建新的合并值实例
     *
     * @param  \Illuminate\Support\Collection|\JsonSerializable|array  $data
     * @return void
     */
    public function __construct($data)
    {
        if ($data instanceof Collection) {
            $this->data = $data->all();
        } elseif ($data instanceof JsonSerializable) {
            $this->data = $data->jsonSerialize();
        } else {
            $this->data = $data;
        }
    }
}
