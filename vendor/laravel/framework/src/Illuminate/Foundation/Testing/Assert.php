<?php
/**
 * 基础，断言抽象类
 */

namespace Illuminate\Foundation\Testing;

use ArrayAccess;
use Illuminate\Foundation\Testing\Constraints\ArraySubset;
use PHPUnit\Framework\Assert as PHPUnit;
use PHPUnit\Framework\Constraint\DirectoryExists;
use PHPUnit\Framework\Constraint\FileExists;
use PHPUnit\Framework\Constraint\LogicalNot;
use PHPUnit\Framework\Constraint\RegularExpression;
use PHPUnit\Framework\InvalidArgumentException;
use PHPUnit\Runner\Version;
use PHPUnit\Util\InvalidArgumentHelper;

if (class_exists(Version::class) && (int) Version::series()[0] >= 8) {
    /**
     * @internal This class is not meant to be used or overwritten outside the framework itself.
     */
    abstract class Assert extends PHPUnit
    {
        /**
         * Asserts that an array has a specified subset.
		 * 断言数组是否具有指定的子集
         *
         * @param  \ArrayAccess|array  $subset
         * @param  \ArrayAccess|array  $array
         * @param  bool  $checkForIdentity
         * @param  string  $msg
         * @return void
         */
        public static function assertArraySubset($subset, $array, bool $checkForIdentity = false, string $msg = ''): void
        {
            if (! (is_array($subset) || $subset instanceof ArrayAccess)) {
                if (class_exists(InvalidArgumentException::class)) {
                    throw InvalidArgumentException::create(1, 'array or ArrayAccess');
                } else {
                    throw InvalidArgumentHelper::factory(1, 'array or ArrayAccess');
                }
            }

            if (! (is_array($array) || $array instanceof ArrayAccess)) {
                if (class_exists(InvalidArgumentException::class)) {
                    throw InvalidArgumentException::create(2, 'array or ArrayAccess');
                } else {
                    throw InvalidArgumentHelper::factory(2, 'array or ArrayAccess');
                }
            }

            $constraint = new ArraySubset($subset, $checkForIdentity);

            PHPUnit::assertThat($array, $constraint, $msg);
        }

        /**
         * Asserts that a file does not exist.
		 * 断言文件不存在
         *
         * @param  string  $filename
         * @param  string  $message
         * @return void
         */
        public static function assertFileDoesNotExist(string $filename, string $message = ''): void
        {
            static::assertThat($filename, new LogicalNot(new FileExists), $message);
        }

        /**
         * Asserts that a directory does not exist.
		 * 断言目录不存在
         *
         * @param  string  $directory
         * @param  string  $message
         * @return void
         */
        public static function assertDirectoryDoesNotExist(string $directory, string $message = ''): void
        {
            static::assertThat($directory, new LogicalNot(new DirectoryExists), $message);
        }

        /**
         * Asserts that a string matches a given regular expression.
		 * 断言字符串是否与给定正则表达式匹配
         *
         * @param  string  $pattern
         * @param  string  $string
         * @param  string  $message
         * @return void
         */
        public static function assertMatchesRegularExpression(string $pattern, string $string, string $message = ''): void
        {
            static::assertThat($string, new RegularExpression($pattern), $message);
        }
    }
} else {
    /**
     * @internal This class is not meant to be used or overwritten outside the framework itself.
     */
    abstract class Assert extends PHPUnit
    {
        /**
         * Asserts that an array has a specified subset.
		 * 断言数组是否具有指定的子集
         *
         * @param  \ArrayAccess|array  $subset
         * @param  \ArrayAccess|array  $array
         * @param  bool  $checkForIdentity
         * @param  string  $msg
         * @return void
         */
        public static function assertArraySubset($subset, $array, bool $checkForIdentity = false, string $msg = ''): void
        {
            PHPUnit::assertArraySubset($subset, $array, $checkForIdentity, $msg);
        }

        /**
         * Asserts that a file does not exist.
		 * 断言文件不存在
         *
         * @param  string  $filename
         * @param  string  $message
         * @return void
         */
        public static function assertFileDoesNotExist(string $filename, string $message = ''): void
        {
            static::assertThat($filename, new LogicalNot(new FileExists), $message);
        }

        /**
         * Asserts that a directory does not exist.
		 * 断言目录不存在
         *
         * @param  string  $directory
         * @param  string  $message
         * @return void
         */
        public static function assertDirectoryDoesNotExist(string $directory, string $message = ''): void
        {
            static::assertThat($directory, new LogicalNot(new DirectoryExists), $message);
        }

        /**
         * Asserts that a string matches a given regular expression.
		 * 断言字符串是否与给定正则表达式匹配
         *
         * @param  string  $pattern
         * @param  string  $string
         * @param  string  $message
         * @return void
         */
        public static function assertMatchesRegularExpression(string $pattern, string $string, string $message = ''): void
        {
            static::assertThat($string, new RegularExpression($pattern), $message);
        }
    }
}
