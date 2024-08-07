<?php
/**
 * 容器登记未找到异常
 */

namespace Illuminate\Container;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

class EntryNotFoundException extends Exception implements NotFoundExceptionInterface
{
    //
}
