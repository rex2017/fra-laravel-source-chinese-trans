<?php
/**
 * 基础，启动
 */

namespace Illuminate\Foundation\Console\Presets;

class Bootstrap extends Preset
{
    /**
     * Install the preset.
	 * 安装预先
     *
     * @return void
     */
    public static function install()
    {
        static::updatePackages();
        static::updateSass();
        static::removeNodeModules();
    }

    /**
     * Update the given package array.
	 * 更新给定包
     *
     * @param  array  $packages
     * @return array
     */
    protected static function updatePackageArray(array $packages)
    {
        return [
            'bootstrap' => '^4.0.0',
            'jquery' => '^3.2',
            'popper.js' => '^1.12',
        ] + $packages;
    }

    /**
     * Update the Sass files for the application.
	 * 更新应用程序的Sass文件
     *
     * @return void
     */
    protected static function updateSass()
    {
        copy(__DIR__.'/bootstrap-stubs/_variables.scss', resource_path('sass/_variables.scss'));
        copy(__DIR__.'/bootstrap-stubs/app.scss', resource_path('sass/app.scss'));
    }
}
