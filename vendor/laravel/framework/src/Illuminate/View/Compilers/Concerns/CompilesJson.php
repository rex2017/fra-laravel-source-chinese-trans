<?php
/**
 * 视图，编译Json
 */

namespace Illuminate\View\Compilers\Concerns;

trait CompilesJson
{
    /**
     * The default JSON encoding options.
	 * 默认的JSON编码选项
     *
     * @var int
     */
    private $encodingOptions = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;

    /**
     * Compile the JSON statement into valid PHP.
	 * 编译JSON语句为有效的PHP
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileJson($expression)
    {
        $parts = explode(',', $this->stripParentheses($expression));

        $options = isset($parts[1]) ? trim($parts[1]) : $this->encodingOptions;

        $depth = isset($parts[2]) ? trim($parts[2]) : 512;

        return "<?php echo json_encode($parts[0], $options, $depth) ?>";
    }
}
