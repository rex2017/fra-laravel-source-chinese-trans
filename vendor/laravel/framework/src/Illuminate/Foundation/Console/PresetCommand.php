<?php
/**
 * 基础，预设命令
 */

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use InvalidArgumentException;

class PresetCommand extends Command
{
    /**
     * The console command signature.
	 & 控制台命令签名
     *
     * @var string
     */
    protected $signature = 'preset
                            { type : The preset type (none, bootstrap, vue, react) }
                            { --option=* : Pass an option to the preset command }';

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Swap the front-end scaffolding for the application';

    /**
     * Execute the console command.
	 * 执行控制台命令
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function handle()
    {
        if (static::hasMacro($this->argument('type'))) {
            return call_user_func(static::$macros[$this->argument('type')], $this);
        }

        if (! in_array($this->argument('type'), ['none', 'bootstrap', 'vue', 'react'])) {
            throw new InvalidArgumentException('Invalid preset.');
        }

        return $this->{$this->argument('type')}();
    }

    /**
     * Install the "fresh" preset.
	 * 安装"fresh"预设
     *
     * @return void
     */
    protected function none()
    {
        Presets\None::install();

        $this->info('Frontend scaffolding removed successfully.');
    }

    /**
     * Install the "bootstrap" preset.
	 * 安装"bootstrap"预设
     *
     * @return void
     */
    protected function bootstrap()
    {
        Presets\Bootstrap::install();

        $this->info('Bootstrap scaffolding installed successfully.');
        $this->comment('Please run "npm install && npm run dev" to compile your fresh scaffolding.');
    }

    /**
     * Install the "vue" preset.
	 * 安装"vue"预设
     *
     * @return void
     */
    protected function vue()
    {
        Presets\Vue::install();

        $this->info('Vue scaffolding installed successfully.');
        $this->comment('Please run "npm install && npm run dev" to compile your fresh scaffolding.');
    }

    /**
     * Install the "react" preset.
	 * 安装"react"预设
     *
     * @return void
     */
    protected function react()
    {
        Presets\React::install();

        $this->info('React scaffolding installed successfully.');
        $this->comment('Please run "npm install && npm run dev" to compile your fresh scaffolding.');
    }
}
