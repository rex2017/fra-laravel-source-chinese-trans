<?php
/**
 * 基础，查看Cache命令
 */

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class ViewCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
	 * 控制台命令的名称和签名
     *
     * @var string
     */
    protected $signature = 'view:cache';

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string
     */
    protected $description = "Compile all of the application's Blade templates";

    /**
     * Execute the console command.
	 * 执行控制台命令
     *
     * @return mixed
     */
    public function handle()
    {
        $this->call('view:clear');

        $this->paths()->each(function ($path) {
            $this->compileViews($this->bladeFilesIn([$path]));
        });

        $this->info('Blade templates cached successfully!');
    }

    /**
     * Compile the given view files.
	 * 编译给定的视图文件
     *
     * @param  \Illuminate\Support\Collection  $views
     * @return void
     */
    protected function compileViews(Collection $views)
    {
        $compiler = $this->laravel['view']->getEngineResolver()->resolve('blade')->getCompiler();

        $views->map(function (SplFileInfo $file) use ($compiler) {
            $compiler->compile($file->getRealPath());
        });
    }

    /**
     * Get the Blade files in the given path.
	 * 得到指定路径下的Blade文件
     *
     * @param  array  $paths
     * @return \Illuminate\Support\Collection
     */
    protected function bladeFilesIn(array $paths)
    {
        return collect(
            Finder::create()
                ->in($paths)
                ->exclude('vendor')
                ->name('*.blade.php')
                ->files()
        );
    }

    /**
     * Get all of the possible view paths.
	 * 得到所有可能的视图路径
     *
     * @return \Illuminate\Support\Collection
     */
    protected function paths()
    {
        $finder = $this->laravel['view']->getFinder();

        return collect($finder->getPaths())->merge(
            collect($finder->getHints())->flatten()
        );
    }
}
