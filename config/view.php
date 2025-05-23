<?php

return [

    /*
    |--------------------------------------------------------------------------
    | View Storage Paths    视图存储路径
    |--------------------------------------------------------------------------
    |
    | Most templating systems load templates from disk. Here you may specify
    | an array of paths that should be checked for your views. Of course
    | the usual Laravel view path has already been registered for you.
    | 大多数模板系统从磁盘导入模板。这里应该为你的视图检查的路径数组。
    | 当然已经为您注册通常的Laravel视图。
    |
    */

    'paths' => [
        resource_path('views'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Compiled View Path    视图编译路径
    |--------------------------------------------------------------------------
    |
    | This option determines where all the compiled Blade templates will be
    | stored for your application. Typically, this is within the storage
    | directory. However, as usual, you are free to change this value.
    | 这个选项决定了你的应用里所有编译后的模板的位置。
    | 通常，这是在存储中。然而，像往常一样，你可以自由地改变这个价值。
    |
    */

    'compiled' => env(
        'VIEW_COMPILED_PATH',
        realpath(storage_path('framework/views'))
    ),

];
