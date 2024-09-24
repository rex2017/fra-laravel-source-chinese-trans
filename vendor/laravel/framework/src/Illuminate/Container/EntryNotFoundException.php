<?php
/**
 * 容器条目未找到异常，待完善类
 */

namespace Illuminate\Container;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

class EntryNotFoundException extends Exception implements NotFoundExceptionInterface
{
    //
}
