<?php
/**
 * 验证，唯一
 */

namespace Illuminate\Validation\Rules;

use Illuminate\Database\Eloquent\Model;

class Unique
{
    use DatabaseRule;

    /**
     * The ID that should be ignored.
	 * 应该被忽略的ID
     *
     * @var mixed
     */
    protected $ignore;

    /**
     * The name of the ID column.
	 * ID列的名称
     *
     * @var string
     */
    protected $idColumn = 'id';

    /**
     * Ignore the given ID during the unique check.
	 * 忽略给定的ID在唯一性检查期间
     *
     * @param  mixed  $id
     * @param  string|null  $idColumn
     * @return $this
     */
    public function ignore($id, $idColumn = null)
    {
        if ($id instanceof Model) {
            return $this->ignoreModel($id, $idColumn);
        }

        $this->ignore = $id;
        $this->idColumn = $idColumn ?? 'id';

        return $this;
    }

    /**
     * Ignore the given model during the unique check.
	 * 在惟一检查期间忽略给定的模型
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string|null  $idColumn
     * @return $this
     */
    public function ignoreModel($model, $idColumn = null)
    {
        $this->idColumn = $idColumn ?? $model->getKeyName();
        $this->ignore = $model->{$this->idColumn};

        return $this;
    }

    /**
     * Convert the rule to a validation string.
	 * 转换规则为验证字符串
     *
     * @return string
     */
    public function __toString()
    {
        return rtrim(sprintf('unique:%s,%s,%s,%s,%s',
            $this->table,
            $this->column,
            $this->ignore ? '"'.addslashes($this->ignore).'"' : 'NULL',
            $this->idColumn,
            $this->formatWheres()
        ), ',');
    }
}
