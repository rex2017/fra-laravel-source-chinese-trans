<?php
/**
 * 数据库，迁移安装命令
 */

namespace Illuminate\Database\Console\Migrations;

use Illuminate\Console\Command;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Symfony\Component\Console\Input\InputOption;

class InstallCommand extends Command
{
    /**
     * The console command name.
	 * 控制台命令名
     *
     * @var string
     */
    protected $name = 'migrate:install';

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Create the migration repository';

    /**
     * The repository instance.
	 * 存储库实例
     *
     * @var \Illuminate\Database\Migrations\MigrationRepositoryInterface
     */
    protected $repository;

    /**
     * Create a new migration install command instance.
	 * 创建新的迁移安装命令实例
     *
     * @param  \Illuminate\Database\Migrations\MigrationRepositoryInterface  $repository
     * @return void
     */
    public function __construct(MigrationRepositoryInterface $repository)
    {
        parent::__construct();

        $this->repository = $repository;
    }

    /**
     * Execute the console command.
	 * 执行控制台命令
     *
     * @return void
     */
    public function handle()
    {
        $this->repository->setSource($this->input->getOption('database'));

        $this->repository->createRepository();

        $this->info('Migration table created successfully.');
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
        ];
    }
}
