<?php
/**
 * 基础，无
 */

namespace Illuminate\Foundation\Console\Presets;

use Illuminate\Filesystem\Filesystem;

class None extends Preset
{
    /**
     * Install the preset.
	 * 安装预设
     *
     * @return void
     */
    public static function install()
    {
        static::updatePackages();
        static::updateBootstrapping();
        static::updateWebpackConfiguration();

        tap(new Filesystem, function ($filesystem) {
            $filesystem->deleteDirectory(resource_path('js/components'));
            $filesystem->delete(resource_path('sass/_variables.scss'));
            $filesystem->deleteDirectory(base_path('node_modules'));
            $filesystem->deleteDirectory(public_path('css'));
            $filesystem->deleteDirectory(public_path('js'));
        });
    }

    /**
     * Update the given package array.
	 * 更新给定的包数组
     *
     * @param  array  $packages
     * @return array
     */
    protected static function updatePackageArray(array $packages)
    {
        unset(
            $packages['bootstrap'],
            $packages['jquery'],
            $packages['popper.js'],
            $packages['vue'],
            $packages['vue-template-compiler'],
            $packages['@babel/preset-react'],
            $packages['react'],
            $packages['react-dom']
        );

        return $packages;
    }

    /**
     * Write the stubs for the Sass and JavaScript files.
	 * 写Sass和JavaScript文件的存根
     *
     * @return void
     */
    protected static function updateBootstrapping()
    {
        file_put_contents(resource_path('sass/app.scss'), ''.PHP_EOL);
        copy(__DIR__.'/none-stubs/app.js', resource_path('js/app.js'));
        copy(__DIR__.'/none-stubs/bootstrap.js', resource_path('js/bootstrap.js'));
    }

    /**
     * Update the Webpack configuration.
	 * 更新Webpack配置
     *
     * @return void
     */
    protected static function updateWebpackConfiguration()
    {
        copy(__DIR__.'/none-stubs/webpack.mix.js', base_path('webpack.mix.js'));
    }
}
