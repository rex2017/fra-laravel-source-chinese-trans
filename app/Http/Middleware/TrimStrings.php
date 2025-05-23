<?php
/**
 * App，Http，修整字符串
 */

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TrimStrings as Middleware;

class TrimStrings extends Middleware
{
    /**
     * The names of the attributes that should not be trimmed.
     * 不应修整的属性的名称
     *
     * @var array
     */
    protected $except = [
        'password',
        'password_confirmation',
    ];
}
