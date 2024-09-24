<?php
/**
 * 数据库，迁移状态命令
 */

namespace Illuminate\Database\Console\Migrations;

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Input\InputOption;

class StatusCommand extends BaseCommand
{
    /**
     * The console command name.
	 * 控制台命令名
     *
     * @var string
     */
    protected $name = 'migrate:status';

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Show the status of each migration';

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
        $this->migrator->setConnection($this->option('database'));

        if (! $this->migrator->repositoryExists()) {
            $this->error('Migration table not found.');

            return 1;
        }

        $ran = $this->migrator->getRepository()->getRan();

        $batches = $this->migrator->getRepository()->getMigrationBatches();

        if (count($migrations = $this->getStatusFor($ran, $batches)) > 0) {
            $this->table(['Ran?', 'Migration', 'Batch'], $migrations);
        } else {
            $this->error('No migrations found');
        }
    }

    /**
     * Get the status for the given ran migrations.
	 * 得到运行迁移的状态
     *
     * @param  array  $ran
     * @param  array  $batches
     * @return \Illuminate\Support\Collection
     */
    protected function getStatusFor(array $ran, array $batches)
    {
        return Collection::make($this->getAllMigrationFiles())
                    ->map(function ($migration) use ($ran, $batches) {
                        $migrationName = $this->migrator->getMigrationName($migration);

                        return in_array($migrationName, $ran)
                                ? ['<info>Yes</info>', $migrationName, $batches[$migrationName]]
                                : ['<fg=red>No</fg=red>', $migrationName];
                    });
    }

    /**
     * Get an array of all of the migration files.
	 * 得到所有迁移文件的数组
     *
     * @return array
     */
    protected function getAllMigrationFiles()
    {
        return $this->migrator->getMigrationFiles($this->getMigrationPaths());
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

            ['path', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'The path(s) to the migrations files to use'],

            ['realpath', null, InputOption::VALUE_NONE, 'Indicate any provided migration file paths are pre-resolved absolute paths'],
        ];
    }
}
