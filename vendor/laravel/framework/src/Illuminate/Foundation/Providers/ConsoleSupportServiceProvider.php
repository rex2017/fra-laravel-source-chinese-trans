<?php
/**
 * 基础，控制台服务提供者
 */

namespace Illuminate\Foundation\Providers;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Database\MigrationServiceProvider;
use Illuminate\Support\AggregateServiceProvider;

class ConsoleSupportServiceProvider extends AggregateServiceProvider implements DeferrableProvider
{
    /**
     * The provider class names.
	 * 提供者类
     *
     * @var array
     */
    protected $providers = [
        ArtisanServiceProvider::class,
        MigrationServiceProvider::class,
        ComposerServiceProvider::class,
    ];
}
