<?php
/**
 * 数据库控，迁移重置命令
 */

namespace Illuminate\Database\Console\Migrations;

use Illuminate\Console\ConfirmableTrait;
use Illuminate\Database\Migrations\Migrator;
use Symfony\Component\Console\Input\InputOption;

class ResetCommand extends BaseCommand
{
    use ConfirmableTrait;

    /**
     * The console command name.
	 * 控制台命令名
     *
     * @var string
     */
    protected $name = 'migrate:reset';

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Rollback all database migrations';

    /**
     * The migrator instance.
	 * 迁移实例
     *
     * @var \Illuminate\Database\Migrations\Migrator
     */
    protected $migrator;

    /**
     * Create a new migration rollback command instance.
	 * 创建新的迁移回滚命令实例
     *
     * @param  \Illuminate\Database\Migrations\Migrator  $migrator
     * @return void
     */
    public function __construct(Migrator $migrator)
    {
        parent::__construct();

        $this->migrator = $migrator;
    }

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

        $this->migrator->setConnection($this->option('database'));

        // First, we'll make sure that the migration table actually exists before we
        // start trying to rollback and re-run all of the migrations. If it's not
        // present we'll just bail out with an info message for the developers.
		// 首先，我们将确保迁移表确定存在，然后再开始尝试回滚并重新运行所有迁移。
		// 如果不是现在，我们只需向开发人员发送一条信息即可。
        if (! $this->migrator->repositoryExists()) {
            return $this->comment('Migration table not found.');
        }

        $this->migrator->setOutput($this->output)->reset(
            $this->getMigrationPaths(), $this->option('pretend')
        );
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

            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production'],

            ['path', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'The path(s) to the migrations files to be executed'],

            ['realpath', null, InputOption::VALUE_NONE, 'Indicate any provided migration file paths are pre-resolved absolute paths'],

            ['pretend', null, InputOption::VALUE_NONE, 'Dump the SQL queries that would be run'],
        ];
    }
}
