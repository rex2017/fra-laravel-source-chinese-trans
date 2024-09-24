<?php
/**
 * 支持，流程工具
 */

namespace Illuminate\Support;

/**
 * ProcessUtils is a bunch of utility methods.
 * ProcessUtils是一堆实用程序方法
 *
 * This class was originally copied from Symfony 3.
 */
class ProcessUtils
{
    /**
     * Escapes a string to be used as a shell argument.
	 * 转义要用作shell参数的字符串
     *
     * @param  string  $argument
     * @return string
     */
    public static function escapeArgument($argument)
    {
        // Fix for PHP bug #43784 escapeshellarg removes % from given string
        // Fix for PHP bug #49446 escapeshellarg doesn't work on Windows
        // @see https://bugs.php.net/bug.php?id=43784
        // @see https://bugs.php.net/bug.php?id=49446
        if ('\\' === DIRECTORY_SEPARATOR) {
            if ('' === $argument) {
                return '""';
            }

            $escapedArgument = '';
            $quote = false;

            foreach (preg_split('/(")/', $argument, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE) as $part) {
                if ('"' === $part) {
                    $escapedArgument .= '\\"';
                } elseif (self::isSurroundedBy($part, '%')) {
                    // Avoid environment variable expansion
                    $escapedArgument .= '^%"'.substr($part, 1, -1).'"^%';
                } else {
                    // escape trailing backslash
                    if ('\\' === substr($part, -1)) {
                        $part .= '\\';
                    }
                    $quote = true;
                    $escapedArgument .= $part;
                }
            }

            if ($quote) {
                $escapedArgument = '"'.$escapedArgument.'"';
            }

            return $escapedArgument;
        }

        return "'".str_replace("'", "'\\''", $argument)."'";
    }

    /**
     * Is the given string surrounded by the given character?
	 * 给定字符串是否被给定字符包围?
     *
     * @param  string  $arg
     * @param  string  $char
     * @return bool
     */
    protected static function isSurroundedBy($arg, $char)
    {
        return 2 < strlen($arg) && $char === $arg[0] && $char === $arg[strlen($arg) - 1];
    }
}
