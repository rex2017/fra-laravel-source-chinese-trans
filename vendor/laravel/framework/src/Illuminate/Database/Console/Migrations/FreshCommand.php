<?php
/**
 * 数据库，迁移最新命令
 */

namespace Illuminate\Database\Console\Migrations;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Symfony\Component\Console\Input\InputOption;

class FreshCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The console command name.
	 * 控制台命令名
     *
     * @var string
     */
    protected $name = 'migrate:fresh';

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Drop all tables and re-run all migrations';

    /**
     * Execute the console command.
	 * 执行控制台命令
     *
     * @return void
     */
    public function handle()
    {
        if (! $this->confirmToProceed()) {
            return;
        }

        $database = $this->input->getOption('database');

        $this->call('db:wipe', array_filter([
            '--database' => $database,
            '--drop-views' => $this->option('drop-views'),
            '--drop-types' => $this->option('drop-types'),
            '--force' => true,
        ]));

        $this->call('migrate', array_filter([
            '--database' => $database,
            '--path' => $this->input->getOption('path'),
            '--realpath' => $this->input->getOption('realpath'),
            '--force' => true,
            '--step' => $this->option('step'),
        ]));

        if ($this->needsSeeding()) {
            $this->runSeeder($database);
        }
    }

    /**
     * Determine if the developer has requested database seeding.
	 * 确定开发人员是否请求了数据库播种
     *
     * @return bool
     */
    protected function needsSeeding()
    {
        return $this->option('seed') || $this->option('seeder');
    }

    /**
     * Run the database seeder command.
	 * 执行数据库播种命令
     *
     * @param  string  $database
     * @return void
     */
    protected function runSeeder($database)
    {
        $this->call('db:seed', array_filter([
            '--database' => $database,
            '--class' => $this->option('seeder') ?: 'DatabaseSeeder',
            '--force' => true,
        ]));
    }

    /**
     * Get the console command options.
	 * 得到控制台命令选项
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use'],
            ['drop-views', null, InputOption::VALUE_NONE, 'Drop all tables and views'],
            ['drop-types', null, InputOption::VALUE_NONE, 'Drop all tables and types (Postgres only)'],
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production'],
            ['path', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'The path(s) to the migrations files to be executed'],
            ['realpath', null, InputOption::VALUE_NONE, 'Indicate any provided migration file paths are pre-resolved absolute paths'],
            ['seed', null, InputOption::VALUE_NONE, 'Indicates if the seed task should be re-run'],
            ['seeder', null, InputOption::VALUE_OPTIONAL, 'The class name of the root seeder'],
            ['step', null, InputOption::VALUE_NONE, 'Force the migrations to be run so they can be rolled back individually'],
        ];
    }
}
