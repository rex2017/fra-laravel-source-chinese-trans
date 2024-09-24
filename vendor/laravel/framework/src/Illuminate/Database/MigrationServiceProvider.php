<?php
/**
 * 数据库迁移服务提供者
 */

namespace Illuminate\Database;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Database\Console\Migrations\FreshCommand;
use Illuminate\Database\Console\Migrations\InstallCommand;
use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Database\Console\Migrations\MigrateMakeCommand;
use Illuminate\Database\Console\Migrations\RefreshCommand;
use Illuminate\Database\Console\Migrations\ResetCommand;
use Illuminate\Database\Console\Migrations\RollbackCommand;
use Illuminate\Database\Console\Migrations\StatusCommand;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\ServiceProvider;

class MigrationServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * The commands to be registered.
	 * 命令被注册
     *
     * @var array
     */
    protected $commands = [
        'Migrate' => 'command.migrate',
        'MigrateFresh' => 'command.migrate.fresh',
        'MigrateInstall' => 'command.migrate.install',
        'MigrateRefresh' => 'command.migrate.refresh',
        'MigrateReset' => 'command.migrate.reset',
        'MigrateRollback' => 'command.migrate.rollback',
        'MigrateStatus' => 'command.migrate.status',
        'MigrateMake' => 'command.migrate.make',
    ];

    /**
     * Register the service provider.
	 * 注册服务提供者
     *
     * @return void
     */
    public function register()
    {
        $this->registerRepository();

        $this->registerMigrator();

        $this->registerCreator();

        $this->registerCommands($this->commands);
    }

    /**
     * Register the migration repository service.
	 * 注册迁移存储库服务
     *
     * @return void
     */
    protected function registerRepository()
    {
        $this->app->singleton('migration.repository', function ($app) {
            $table = $app['config']['database.migrations'];

            return new DatabaseMigrationRepository($app['db'], $table);
        });
    }

    /**
     * Register the migrator service.
	 * 注册迁移服务
     *
     * @return void
     */
    protected function registerMigrator()
    {
        // The migrator is responsible for actually running and rollback the migration
        // files in the application. We'll pass in our database connection resolver
        // so the migrator can resolve any of these connections when it needs to.
		// 迁移器负责实际运行的回滚应用程序中的迁移文件。
		// 我们将传入数据库连接分析器，因此，迁移器可以在需要时解析任何这样的连接。
        $this->app->singleton('migrator', function ($app) {
            $repository = $app['migration.repository'];

            return new Migrator($repository, $app['db'], $app['files'], $app['events']);
        });
    }

    /**
     * Register the migration creator.
	 * 注册迁移创建者
     *
     * @return void
     */
    protected function registerCreator()
    {
        $this->app->singleton('migration.creator', function ($app) {
            return new MigrationCreator($app['files']);
        });
    }

    /**
     * Register the given commands.
	 * 注册给定命令
     *
     * @param  array  $commands
     * @return void
     */
    protected function registerCommands(array $commands)
    {
        foreach (array_keys($commands) as $command) {
            $this->{"register{$command}Command"}();
        }

        $this->commands(array_values($commands));
    }

    /**
     * Register the command.
	 * 注册迁移命令
     *
     * @return void
     */
    protected function registerMigrateCommand()
    {
        $this->app->singleton('command.migrate', function ($app) {
            return new MigrateCommand($app['migrator']);
        });
    }

    /**
     * Register the command.
	 * 注册迁移命令
     *
     * @return void
     */
    protected function registerMigrateFreshCommand()
    {
        $this->app->singleton('command.migrate.fresh', function () {
            return new FreshCommand;
        });
    }

    /**
     * Register the command.
	 * 注册迁移安装命令
     *
     * @return void
     */
    protected function registerMigrateInstallCommand()
    {
        $this->app->singleton('command.migrate.install', function ($app) {
            return new InstallCommand($app['migration.repository']);
        });
    }

    /**
     * Register the command.
	 * 注册迁移生成命令
     *
     * @return void
     */
    protected function registerMigrateMakeCommand()
    {
        $this->app->singleton('command.migrate.make', function ($app) {
            // Once we have the migration creator registered, we will create the command
            // and inject the creator. The creator is responsible for the actual file
            // creation of the migrations, and may be extended by these developers.
			// 一旦我们注册了迁移创建者，我们将创建命令和注入创造者。
			// 创建者负责迁移的实际文件创建，并且可以由这些开发人员进行扩展。
            $creator = $app['migration.creator'];

            $composer = $app['composer'];

            return new MigrateMakeCommand($creator, $composer);
        });
    }

    /**
     * Register the command.
	 * 注册迁移刷新命令
     *
     * @return void
     */
    protected function registerMigrateRefreshCommand()
    {
        $this->app->singleton('command.migrate.refresh', function () {
            return new RefreshCommand;
        });
    }

    /**
     * Register the command.
	 * 注册迁移重置命令
     *
     * @return void
     */
    protected function registerMigrateResetCommand()
    {
        $this->app->singleton('command.migrate.reset', function ($app) {
            return new ResetCommand($app['migrator']);
        });
    }

    /**
     * Register the command.
	 * 注册迁移回调命令
     *
     * @return void
     */
    protected function registerMigrateRollbackCommand()
    {
        $this->app->singleton('command.migrate.rollback', function ($app) {
            return new RollbackCommand($app['migrator']);
        });
    }

    /**
     * Register the command.
	 * 注册迁移状态命令
     *
     * @return void
     */
    protected function registerMigrateStatusCommand()
    {
        $this->app->singleton('command.migrate.status', function ($app) {
            return new StatusCommand($app['migrator']);
        });
    }

    /**
     * Get the services provided by the provider.
	 * 得到服务提供者
     *
     * @return array
     */
    public function provides()
    {
        return array_merge([
            'migrator', 'migration.repository', 'migration.creator',
        ], array_values($this->commands));
    }
}
