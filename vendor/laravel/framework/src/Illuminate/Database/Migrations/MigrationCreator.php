<?php
/**
 * 数据库，迁移创建者
 */

namespace Illuminate\Database\Migrations;

use Closure;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use InvalidArgumentException;

class MigrationCreator
{
    /**
     * The filesystem instance.
	 * 文件系统实例
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The registered post create hooks.
	 * 注册的帖子创建钩子
     *
     * @var array
     */
    protected $postCreate = [];

    /**
     * Create a new migration creator instance.
	 * 创建新的迁移创建者实例
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        $this->files = $files;
    }

    /**
     * Create a new migration at the given path.
	 * 创建新的迁移在给定路径上
     *
     * @param  string  $name
     * @param  string  $path
     * @param  string|null  $table
     * @param  bool  $create
     * @return string
     *
     * @throws \Exception
     */
    public function create($name, $path, $table = null, $create = false)
    {
        $this->ensureMigrationDoesntAlreadyExist($name, $path);

        // First we will get the stub file for the migration, which serves as a type
        // of template for the migration. Once we have those we will populate the
        // various place-holders, save the file, and run the post create event.
		// 首先，我们将获得迁移的存根文件，它作为迁移的一种模板。
		// 一旦我们有了这些，我们将填充各种占位符，保存文件，并运行post-create事件。
        $stub = $this->getStub($table, $create);

        $this->files->put(
            $path = $this->getPath($name, $path),
            $this->populateStub($name, $stub, $table)
        );

        // Next, we will fire any hooks that are supposed to fire after a migration is
        // created. Once that is done we'll be ready to return the full path to the
        // migration file so it can be used however it's needed by the developer.
		// 接下来，我们将启动任何在创建迁移后应该启动的钩子。
		// 一旦完成，我们将准备返回迁移文件的完整路径，以便开发人员可以根据需要使用它。
        $this->firePostCreateHooks($table);

        return $path;
    }

    /**
     * Ensure that a migration with the given name doesn't already exist.
	 * 确保具有给定名称的迁移不存在
     *
     * @param  string  $name
     * @param  string  $migrationPath
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    protected function ensureMigrationDoesntAlreadyExist($name, $migrationPath = null)
    {
        if (! empty($migrationPath)) {
            $migrationFiles = $this->files->glob($migrationPath.'/*.php');

            foreach ($migrationFiles as $migrationFile) {
                $this->files->requireOnce($migrationFile);
            }
        }

        if (class_exists($className = $this->getClassName($name))) {
            throw new InvalidArgumentException("A {$className} class already exists.");
        }
    }

    /**
     * Get the migration stub file.
	 * 得到迁移根文件
     *
     * @param  string|null  $table
     * @param  bool  $create
     * @return string
     */
    protected function getStub($table, $create)
    {
        if (is_null($table)) {
            return $this->files->get($this->stubPath().'/blank.stub');
        }

        // We also have stubs for creating new tables and modifying existing tables
        // to save the developer some typing when they are creating a new tables
        // or modifying existing tables. We'll grab the appropriate stub here.
		// 我们还有用于创建新表和修改现有表的存根，
		// 以便在开发人员创建新表或修改现有表时为他们节省一些打字时间。我们在这里拿合适的存根。
        $stub = $create ? 'create.stub' : 'update.stub';

        return $this->files->get($this->stubPath()."/{$stub}");
    }

    /**
     * Populate the place-holders in the migration stub.
	 * 在迁移存根中填充占位符
     *
     * @param  string  $name
     * @param  string  $stub
     * @param  string|null  $table
     * @return string
     */
    protected function populateStub($name, $stub, $table)
    {
        $stub = str_replace('DummyClass', $this->getClassName($name), $stub);

        // Here we will replace the table place-holders with the table specified by
        // the developer, which is useful for quickly creating a tables creation
        // or update migration from the console instead of typing it manually.
		// 在这里，我们将用开发人员指定的表替换表占位符，
		// 这对于从控制台快速创建表创建或更新迁移非常有用，而不是手动键入。
        if (! is_null($table)) {
            $stub = str_replace('DummyTable', $table, $stub);
        }

        return $stub;
    }

    /**
     * Get the class name of a migration name.
	 * 得到迁移类名
     *
     * @param  string  $name
     * @return string
     */
    protected function getClassName($name)
    {
        return Str::studly($name);
    }

    /**
     * Get the full path to the migration.
	 * 得到迁移的完整路径
     *
     * @param  string  $name
     * @param  string  $path
     * @return string
     */
    protected function getPath($name, $path)
    {
        return $path.'/'.$this->getDatePrefix().'_'.$name.'.php';
    }

    /**
     * Fire the registered post create hooks.
	 * 触发注册的帖子创建钩子
     *
     * @param  string|null  $table
     * @return void
     */
    protected function firePostCreateHooks($table)
    {
        foreach ($this->postCreate as $callback) {
            $callback($table);
        }
    }

    /**
     * Register a post migration create hook.
	 * 注册一个迁移后创建钩子
     *
     * @param  \Closure  $callback
     * @return void
     */
    public function afterCreate(Closure $callback)
    {
        $this->postCreate[] = $callback;
    }

    /**
     * Get the date prefix for the migration.
	 * 得到迁移的日期前缀
     *
     * @return string
     */
    protected function getDatePrefix()
    {
        return date('Y_m_d_His');
    }

    /**
     * Get the path to the stubs.
	 * 得到存根的路径
     *
     * @return string
     */
    public function stubPath()
    {
        return __DIR__.'/stubs';
    }

    /**
     * Get the filesystem instance.
	 * 得到文件系统实例
     *
     * @return \Illuminate\Filesystem\Filesystem
     */
    public function getFilesystem()
    {
        return $this->files;
    }
}
