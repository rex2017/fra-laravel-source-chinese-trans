<?php
/**
 * 数据库，种子命令
 */

namespace Illuminate\Database\Console\Seeds;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Database\ConnectionResolverInterface as Resolver;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Console\Input\InputOption;

class SeedCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The console command name.
	 * 控制台命令名
     *
     * @var string
     */
    protected $name = 'db:seed';

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Seed the database with records';

    /**
     * The connection resolver instance.
	 * 连接解析实例
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    protected $resolver;

    /**
     * Create a new database seed command instance.
	 * 创建新的数据库播种命令实例
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     * @return void
     */
    public function __construct(Resolver $resolver)
    {
        parent::__construct();

        $this->resolver = $resolver;
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

        $this->resolver->setDefaultConnection($this->getDatabase());

        Model::unguarded(function () {
            $this->getSeeder()->__invoke();
        });

        $this->info('Database seeding completed successfully.');
    }

    /**
     * Get a seeder instance from the container.
	 * 得到播种实例
     *
     * @return \Illuminate\Database\Seeder
     */
    protected function getSeeder()
    {
        $class = $this->laravel->make($this->input->getOption('class'));

        return $class->setContainer($this->laravel)->setCommand($this);
    }

    /**
     * Get the name of the database connection to use.
	 * 得到数据库连接名称
     *
     * @return string
     */
    protected function getDatabase()
    {
        $database = $this->input->getOption('database');

        return $database ?: $this->laravel['config']['database.default'];
    }

    /**
     * Get the console command options.
	 * 得到控制台选项
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['class', null, InputOption::VALUE_OPTIONAL, 'The class name of the root seeder', 'DatabaseSeeder'],

            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to seed'],

            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production'],
        ];
    }
}
