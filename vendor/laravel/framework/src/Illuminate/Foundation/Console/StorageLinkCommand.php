<?php
/**
 * 基础，存储链路命令
 */

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;

class StorageLinkCommand extends Command
{
    /**
     * The console command signature.
	 * 控制台命令签名
     *
     * @var string
     */
    protected $signature = 'storage:link';

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Create a symbolic link from "public/storage" to "storage/app/public"';

    /**
     * Execute the console command.
	 * 执行控制台命令
     *
     * @return void
     */
    public function handle()
    {
        if (file_exists(public_path('storage'))) {
            return $this->error('The "public/storage" directory already exists.');
        }

        if (is_link(public_path('storage'))) {
            $this->laravel->make('files')->delete(public_path('storage'));
        }

        $this->laravel->make('files')->link(
            storage_path('app/public'), public_path('storage')
        );

        $this->info('The [public/storage] directory has been linked.');
    }
}
