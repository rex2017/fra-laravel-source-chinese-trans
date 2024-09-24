<?php
/**
 * 基础，包发现命令
 */

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Foundation\PackageManifest;

class PackageDiscoverCommand extends Command
{
    /**
     * The console command signature.
	 * 控制台命令签名
     *
     * @var string
     */
    protected $signature = 'package:discover';

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Rebuild the cached package manifest';

    /**
     * Execute the console command.
	 * 执行控制台命令
     *
     * @param  \Illuminate\Foundation\PackageManifest  $manifest
     * @return void
     */
    public function handle(PackageManifest $manifest)
    {
        $manifest->build();

        foreach (array_keys($manifest->manifest) as $package) {
            $this->line("Discovered Package: <info>{$package}</info>");
        }

        $this->info('Package manifest generated successfully.');
    }
}
