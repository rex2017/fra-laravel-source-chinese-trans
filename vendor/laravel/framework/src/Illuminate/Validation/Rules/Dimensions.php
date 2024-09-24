<?php
/**
 * 验证，维度
 */

namespace Illuminate\Validation\Rules;

class Dimensions
{
    /**
     * The constraints for the dimensions rule.
	 * 维度的约束规则
     *
     * @var array
     */
    protected $constraints = [];

    /**
     * Create a new dimensions rule instance.
	 * 创建新的维度规则实例
     *
     * @param  array  $constraints
     * @return void
     */
    public function __construct(array $constraints = [])
    {
        $this->constraints = $constraints;
    }

    /**
     * Set the "width" constraint.
	 * 设置"宽度"约束
     *
     * @param  int  $value
     * @return $this
     */
    public function width($value)
    {
        $this->constraints['width'] = $value;

        return $this;
    }

    /**
     * Set the "height" constraint.
	 * 设置"高度"约束
     *
     * @param  int  $value
     * @return $this
     */
    public function height($value)
    {
        $this->constraints['height'] = $value;

        return $this;
    }

    /**
     * Set the "min width" constraint.
	 * 设置"最小宽度"约束
     *
     * @param  int  $value
     * @return $this
     */
    public function minWidth($value)
    {
        $this->constraints['min_width'] = $value;

        return $this;
    }

    /**
     * Set the "min height" constraint.
	 * 设置"最小高度"约束
     *
     * @param  int  $value
     * @return $this
     */
    public function minHeight($value)
    {
        $this->constraints['min_height'] = $value;

        return $this;
    }

    /**
     * Set the "max width" constraint.
	 * 设置"最大宽度"约束
     *
     * @param  int  $value
     * @return $this
     */
    public function maxWidth($value)
    {
        $this->constraints['max_width'] = $value;

        return $this;
    }

    /**
     * Set the "max height" constraint.
	 * 设置"最大高度"约束
     *
     * @param  int  $value
     * @return $this
     */
    public function maxHeight($value)
    {
        $this->constraints['max_height'] = $value;

        return $this;
    }

    /**
     * Set the "ratio" constraint.
	 * 设置"比率"约束
     *
     * @param  float  $value
     * @return $this
     */
    public function ratio($value)
    {
        $this->constraints['ratio'] = $value;

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
        $result = '';

        foreach ($this->constraints as $key => $value) {
            $result .= "$key=$value,";
        }

        return 'dimensions:'.substr($result, 0, -1);
    }
}
