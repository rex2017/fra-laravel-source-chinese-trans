<?php
/**
 * 数据库，Eloquent模型抽象类，定义Eloquent模型的基本结构和方法
 */

namespace Illuminate\Database\Eloquent;

use ArrayAccess;
use Exception;
use Illuminate\Contracts\Queue\QueueableCollection;
use Illuminate\Contracts\Queue\QueueableEntity;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Database\ConnectionResolverInterface as Resolver;
use Illuminate\Database\Eloquent\Relations\Concerns\AsPivot;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;
use JsonSerializable;

abstract class Model implements Arrayable, ArrayAccess, Jsonable, JsonSerializable, QueueableEntity, UrlRoutable
{
    use Concerns\HasAttributes,
        Concerns\HasEvents,
        Concerns\HasGlobalScopes,
        Concerns\HasRelationships,
        Concerns\HasTimestamps,
        Concerns\HidesAttributes,
        Concerns\GuardsAttributes,
        ForwardsCalls;

    /**
     * The connection name for the model.
	 * 模型连接名
     *
     * @var string|null
     */
    protected $connection;

    /**
     * The table associated with the model.
	 * 模型相关联的表
     *
     * @var string
     */
    protected $table;

    /**
     * The primary key for the model.
	 * 模型主键
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The "type" of the primary key ID.
	 * 主键ID类型
     *
     * @var string
     */
    protected $keyType = 'int';

    /**
     * Indicates if the IDs are auto-incrementing.
	 * 指明是否ID为自动自增
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The relations to eager load on every query.
	 * eager的关系在每个查询上加载
     *
     * @var array
     */
    protected $with = [];

    /**
     * The relationship counts that should be eager loaded on every query.
	 * 应该在每个查询上立即加载的关系计数
     *
     * @var array
     */
    protected $withCount = [];

    /**
     * The number of models to return for pagination.
	 * 返回分页数
     *
     * @var int
     */
    protected $perPage = 15;

    /**
     * Indicates if the model exists.
	 * 指明模型是否存在
     *
     * @var bool
     */
    public $exists = false;

    /**
     * Indicates if the model was inserted during the current request lifecycle.
	 * 指明模型是否在当前请求生命周期中插入
     *
     * @var bool
     */
    public $wasRecentlyCreated = false;

    /**
     * The connection resolver instance.
	 * 连接解析器实例
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    protected static $resolver;

    /**
     * The event dispatcher instance.
	 * 事件调度实例
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected static $dispatcher;

    /**
     * The array of booted models.
	 * 启动模型的数组
     *
     * @var array
     */
    protected static $booted = [];

    /**
     * The array of trait initializers that will be called on each new instance.
	 * 将在每个新实例上调用的trait初始化器数组
     *
     * @var array
     */
    protected static $traitInitializers = [];

    /**
     * The array of global scopes on the model.
	 * 模型上的全局作用域数组
     *
     * @var array
     */
    protected static $globalScopes = [];

    /**
     * The list of models classes that should not be affected with touch.
	 * 不受touch影响的模型类列表
     *
     * @var array
     */
    protected static $ignoreOnTouch = [];

    /**
     * The name of the "created at" column.
	 * 列名称-创建
     *
     * @var string|null
     */
    const CREATED_AT = 'created_at';

    /**
     * The name of the "updated at" column.
	 * 列名称-更新
     *
     * @var string|null
     */
    const UPDATED_AT = 'updated_at';

    /**
     * Create a new Eloquent model instance.
	 * 创建新的Eloquent模型实例
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        $this->bootIfNotBooted();

        $this->initializeTraits();

        $this->syncOriginal();

        $this->fill($attributes);
    }

    /**
     * Check if the model needs to be booted and if so, do it.
	 * 检查模型是否需要启动，如果需要就启动
     *
     * @return void
     */
    protected function bootIfNotBooted()
    {
        if (! isset(static::$booted[static::class])) {
            static::$booted[static::class] = true;

            $this->fireModelEvent('booting', false);

            static::boot();

            $this->fireModelEvent('booted', false);
        }
    }

    /**
     * The "booting" method of the model.
	 * 模型的"启动"方法
     *
     * @return void
     */
    protected static function boot()
    {
        static::bootTraits();
    }

    /**
     * Boot all of the bootable traits on the model.
	 * 启动模型上所有可启动的特征
     *
     * @return void
     */
    protected static function bootTraits()
    {
        $class = static::class;

        $booted = [];

        static::$traitInitializers[$class] = [];

        foreach (class_uses_recursive($class) as $trait) {
            $method = 'boot'.class_basename($trait);

            if (method_exists($class, $method) && ! in_array($method, $booted)) {
                forward_static_call([$class, $method]);

                $booted[] = $method;
            }

            if (method_exists($class, $method = 'initialize'.class_basename($trait))) {
                static::$traitInitializers[$class][] = $method;

                static::$traitInitializers[$class] = array_unique(
                    static::$traitInitializers[$class]
                );
            }
        }
    }

    /**
     * Initialize any initializable traits on the model.
	 * 初始化模型上任何可初始化的特征
     *
     * @return void
     */
    protected function initializeTraits()
    {
        foreach (static::$traitInitializers[static::class] as $method) {
            $this->{$method}();
        }
    }

    /**
     * Clear the list of booted models so they will be re-booted.
	 * 清除已启动的模型列表，以便它们将被重新启动。
     *
     * @return void
     */
    public static function clearBootedModels()
    {
        static::$booted = [];

        static::$globalScopes = [];
    }

    /**
     * Disables relationship model touching for the current class during given callback scope.
	 * 在给定的回调范围内禁用当前类的关系模型触摸
     *
     * @param  callable  $callback
     * @return void
     */
    public static function withoutTouching(callable $callback)
    {
        static::withoutTouchingOn([static::class], $callback);
    }

    /**
     * Disables relationship model touching for the given model classes during given callback scope.
	 * 在给定的回调范围内，为给定的模型类禁用关系模型触摸。
     *
     * @param  array  $models
     * @param  callable  $callback
     * @return void
     */
    public static function withoutTouchingOn(array $models, callable $callback)
    {
        static::$ignoreOnTouch = array_values(array_merge(static::$ignoreOnTouch, $models));

        try {
            $callback();
        } finally {
            static::$ignoreOnTouch = array_values(array_diff(static::$ignoreOnTouch, $models));
        }
    }

    /**
     * Determine if the given model is ignoring touches.
	 * 确定给定模型是否忽略触摸
     *
     * @param  string|null  $class
     * @return bool
     */
    public static function isIgnoringTouch($class = null)
    {
        $class = $class ?: static::class;

        if (! get_class_vars($class)['timestamps'] || ! $class::UPDATED_AT) {
            return true;
        }

        foreach (static::$ignoreOnTouch as $ignoredClass) {
            if ($class === $ignoredClass || is_subclass_of($class, $ignoredClass)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fill the model with an array of attributes.
	 * 用属性数组填充模型
     *
     * @param  array  $attributes
     * @return $this
     *
     * @throws \Illuminate\Database\Eloquent\MassAssignmentException
     */
    public function fill(array $attributes)
    {
        $totallyGuarded = $this->totallyGuarded();

        foreach ($this->fillableFromArray($attributes) as $key => $value) {
            $key = $this->removeTableFromKey($key);

            // The developers may choose to place some attributes in the "fillable" array
            // which means only those attributes may be set through mass assignment to
            // the model, and all others will just get ignored for security reasons.
			// 开发人员可以选择将一些属性放置在“可填充”数组中，
			// 只有这些属性可以通过大规模分配给模型来设置，而所有其他属性都会因安全原因而被忽略。
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            } elseif ($totallyGuarded) {
                throw new MassAssignmentException(sprintf(
                    'Add [%s] to fillable property to allow mass assignment on [%s].',
                    $key, get_class($this)
                ));
            }
        }

        return $this;
    }

    /**
     * Fill the model with an array of attributes. Force mass assignment.
	 * 用属性数组填充模型。强制质量分配。
     *
     * @param  array  $attributes
     * @return $this
     */
    public function forceFill(array $attributes)
    {
        return static::unguarded(function () use ($attributes) {
            return $this->fill($attributes);
        });
    }

    /**
     * Qualify the given column name by the model's table.
	 * 验证给定的列名通过模型的表
     *
     * @param  string  $column
     * @return string
     */
    public function qualifyColumn($column)
    {
        if (Str::contains($column, '.')) {
            return $column;
        }

        return $this->getTable().'.'.$column;
    }

    /**
     * Remove the table name from a given key.
	 * 删除表名从给定键中
     *
     * @param  string  $key
     * @return string
     */
    protected function removeTableFromKey($key)
    {
        return $key;
    }

    /**
     * Create a new instance of the given model.
	 * 创建给定模型的新实例
     *
     * @param  array  $attributes
     * @param  bool  $exists
     * @return static
     */
    public function newInstance($attributes = [], $exists = false)
    {
        // This method just provides a convenient way for us to generate fresh model
        // instances of this current model. It is particularly useful during the
        // hydration of new objects via the Eloquent query builder instances.
		// 这种方法为我们生成当前模型的新模型提供了一种方便的方法。
		// 在通过Eloquent查询构建器实例水合新对象时，它特别有用。
        $model = new static((array) $attributes);

        $model->exists = $exists;

        $model->setConnection(
            $this->getConnectionName()
        );

        $model->setTable($this->getTable());

        return $model;
    }

    /**
     * Create a new model instance that is existing.
	 * 创建一个现有的新模型实例
     *
     * @param  array  $attributes
     * @param  string|null  $connection
     * @return static
     */
    public function newFromBuilder($attributes = [], $connection = null)
    {
        $model = $this->newInstance([], true);

        $model->setRawAttributes((array) $attributes, true);

        $model->setConnection($connection ?: $this->getConnectionName());

        $model->fireModelEvent('retrieved', false);

        return $model;
    }

    /**
     * Begin querying the model on a given connection.
	 * 开始查询给定连接上的模型
     *
     * @param  string|null  $connection
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function on($connection = null)
    {
        // First we will just create a fresh instance of this model, and then we can set the
        // connection on the model so that it is used for the queries we execute, as well
        // as being set on every relation we retrieve without a custom connection name.
		// 首先，我们将创建此模型的一个新实例，然后我们可以在模型上设置连接，
		// 以便它用于我们执行的查询，以及在没有自定义连接名称的情况下对我们检索到的每个关系进行设置。
        $instance = new static;

        $instance->setConnection($connection);

        return $instance->newQuery();
    }

    /**
     * Begin querying the model on the write connection.
	 * 开始查询写连接上的模型
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public static function onWriteConnection()
    {
        return static::query()->useWritePdo();
    }

    /**
     * Get all of the models from the database.
	 * 得到所有模型从数据库中
     *
     * @param  array|mixed  $columns
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public static function all($columns = ['*'])
    {
        return static::query()->get(
            is_array($columns) ? $columns : func_get_args()
        );
    }

    /**
     * Begin querying a model with eager loading.
	 * 开始查询具有即时加载的模型
     *
     * @param  array|string  $relations
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function with($relations)
    {
        return static::query()->with(
            is_string($relations) ? func_get_args() : $relations
        );
    }

    /**
     * Eager load relations on the model.
	 * 模型上的急切载荷关系
     *
     * @param  array|string  $relations
     * @return $this
     */
    public function load($relations)
    {
        $query = $this->newQueryWithoutRelationships()->with(
            is_string($relations) ? func_get_args() : $relations
        );

        $query->eagerLoadRelations([$this]);

        return $this;
    }

    /**
     * Eager load relations on the model if they are not already eager loaded.
	 * 模型上的急切加载关系，如果它们还没有急切加载的话。
     *
     * @param  array|string  $relations
     * @return $this
     */
    public function loadMissing($relations)
    {
        $relations = is_string($relations) ? func_get_args() : $relations;

        $this->newCollection([$this])->loadMissing($relations);

        return $this;
    }

    /**
     * Eager load relation counts on the model.
	 * 急切负荷关系依赖于模型
     *
     * @param  array|string  $relations
     * @return $this
     */
    public function loadCount($relations)
    {
        $relations = is_string($relations) ? func_get_args() : $relations;

        $this->newCollection([$this])->loadCount($relations);

        return $this;
    }

    /**
     * Increment a column's value by a given amount.
	 * 将列的值增加给定的量
     *
     * @param  string  $column
     * @param  float|int  $amount
     * @param  array  $extra
     * @return int
     */
    protected function increment($column, $amount = 1, array $extra = [])
    {
        return $this->incrementOrDecrement($column, $amount, $extra, 'increment');
    }

    /**
     * Decrement a column's value by a given amount.
	 * 将列的值递减给定的量
     *
     * @param  string  $column
     * @param  float|int  $amount
     * @param  array  $extra
     * @return int
     */
    protected function decrement($column, $amount = 1, array $extra = [])
    {
        return $this->incrementOrDecrement($column, $amount, $extra, 'decrement');
    }

    /**
     * Run the increment or decrement method on the model.
	 * 运行增量或递减方法在模型上
     *
     * @param  string  $column
     * @param  float|int  $amount
     * @param  array  $extra
     * @param  string  $method
     * @return int
     */
    protected function incrementOrDecrement($column, $amount, $extra, $method)
    {
        $query = $this->newQueryWithoutRelationships();

        if (! $this->exists) {
            return $query->{$method}($column, $amount, $extra);
        }

        $this->incrementOrDecrementAttributeValue($column, $amount, $extra, $method);

        return $query->where(
            $this->getKeyName(), $this->getKey()
        )->{$method}($column, $amount, $extra);
    }

    /**
     * Increment the underlying attribute value and sync with original.
	 * 增加底层属性值并与原始属性同步
     *
     * @param  string  $column
     * @param  float|int  $amount
     * @param  array  $extra
     * @param  string  $method
     * @return void
     */
    protected function incrementOrDecrementAttributeValue($column, $amount, $extra, $method)
    {
        $this->{$column} = $this->{$column} + ($method === 'increment' ? $amount : $amount * -1);

        $this->forceFill($extra);

        $this->syncOriginalAttribute($column);
    }

    /**
     * Update the model in the database.
	 * 更新模型
     *
     * @param  array  $attributes
     * @param  array  $options
     * @return bool
     */
    public function update(array $attributes = [], array $options = [])
    {
        if (! $this->exists) {
            return false;
        }

        return $this->fill($attributes)->save($options);
    }

    /**
     * Save the model and all of its relationships.
	 * 保存模型及其所有关系
     *
     * @return bool
     */
    public function push()
    {
        if (! $this->save()) {
            return false;
        }

        // To sync all of the relationships to the database, we will simply spin through
        // the relationships and save each model via this "push" method, which allows
        // us to recurse into all of these nested relations for the model instance.
		// 要将所有关系同步到数据库，我们只需旋转即可通过这种"推送"方法保存每个模型，
		// 该方法允许我们将递归到模型实例的所有这些嵌套关系中。
        foreach ($this->relations as $models) {
            $models = $models instanceof Collection
                        ? $models->all() : [$models];

            foreach (array_filter($models) as $model) {
                if (! $model->push()) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Save the model to the database.
	 * 保存模型到数据库
     *
     * @param  array  $options
     * @return bool
     */
    public function save(array $options = [])
    {
        $query = $this->newModelQuery();

        // If the "saving" event returns false we'll bail out of the save and return
        // false, indicating that the save failed. This provides a chance for any
        // listeners to cancel save operations if validations fail or whatever.
		// 如果“save”事件返回false，我们将退出保存并返回false，表示保存失败。
		// 如果验证失败或发生其他情况，这为任何侦听器提供了取消保存操作的机会。
        if ($this->fireModelEvent('saving') === false) {
            return false;
        }

        // If the model already exists in the database we can just update our record
        // that is already in this database using the current IDs in this "where"
        // clause to only update this model. Otherwise, we'll just insert them.
		// 如果模型已存在于数据库中，我们可以使用此“where”子句中的当前ID更新已存在于此数据库中的记录，
		// 仅更新此模型。否则，我们只需插入它们。
        if ($this->exists) {
            $saved = $this->isDirty() ?
                        $this->performUpdate($query) : true;
        }

        // If the model is brand new, we'll insert it into our database and set the
        // ID attribute on the model to the value of the newly inserted row's ID
        // which is typically an auto-increment value managed by the database.
		// 如果模型是全新的，我们会将其插入数据库，并将模型的ID属性设置为新插入行的ID值，
		// 该值通常是由数据库管理的自动增量值。
        else {
            $saved = $this->performInsert($query);

            if (! $this->getConnectionName() &&
                $connection = $query->getConnection()) {
                $this->setConnection($connection->getName());
            }
        }

        // If the model is successfully saved, we need to do a few more things once
        // that is done. We will call the "saved" method here to run any actions
        // we need to happen after a model gets successfully saved right here.
		// 如果模型成功保存，我们需要在完成后再做几件事。我们将在此处调用"saved"方法，
		// 以运行模型成功保存后需要执行的任何操作。
        if ($saved) {
            $this->finishSave($options);
        }

        return $saved;
    }

    /**
     * Save the model to the database using transaction.
	 * 保存模型到数据库中使用事务
     *
     * @param  array  $options
     * @return bool
     *
     * @throws \Throwable
     */
    public function saveOrFail(array $options = [])
    {
        return $this->getConnection()->transaction(function () use ($options) {
            return $this->save($options);
        });
    }

    /**
     * Perform any actions that are necessary after the model is saved.
	 * 在保存模型后执行任何必要的操作
     *
     * @param  array  $options
     * @return void
     */
    protected function finishSave(array $options)
    {
        $this->fireModelEvent('saved', false);

        if ($this->isDirty() && ($options['touch'] ?? true)) {
            $this->touchOwners();
        }

        $this->syncOriginal();
    }

    /**
     * Perform a model update operation.
	 * 执行一个模型更新操作
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return bool
     */
    protected function performUpdate(Builder $query)
    {
        // If the updating event returns false, we will cancel the update operation so
        // developers can hook Validation systems into their models and cancel this
        // operation if the model does not pass validation. Otherwise, we update.
		// 如果更新事件返回false，我们将取消更新操作，以便开发人员可以将验证系统挂接到他们的模型中，
		// 并在模型未通过验证时取消此操作。否则，我们将进行更新。
        if ($this->fireModelEvent('updating') === false) {
            return false;
        }

        // First we need to create a fresh query instance and touch the creation and
        // update timestamp on the model which are maintained by us for developer
        // convenience. Then we will just continue saving the model instances.
		// 首先，我们需要创建一个新的查询实例，并触摸模型上的创建和更新时间戳，
		// 这些时间戳由我们维护，以方便开发人员。然后，我们将继续保存模型实例。
        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
        }

        // Once we have run the update operation, we will fire the "updated" event for
        // this model instance. This will allow developers to hook into these after
        // models are updated, giving them a chance to do any special processing.
		// 一旦我们运行了更新操作，我们将为此模型实例触发"updated"事件。
		// 这将允许开发人员在模型更新后连接到这些，让他们有机会进行任何特殊处理。
        $dirty = $this->getDirty();

        if (count($dirty) > 0) {
            $this->setKeysForSaveQuery($query)->update($dirty);

            $this->syncChanges();

            $this->fireModelEvent('updated', false);
        }

        return true;
    }

    /**
     * Set the keys for a save update query.
	 * 为保存更新查询设置键
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function setKeysForSaveQuery(Builder $query)
    {
        $query->where($this->getKeyName(), '=', $this->getKeyForSaveQuery());

        return $query;
    }

    /**
     * Get the primary key value for a save query.
	 * 得到保存查询的主键值
     *
     * @return mixed
     */
    protected function getKeyForSaveQuery()
    {
        return $this->original[$this->getKeyName()]
                        ?? $this->getKey();
    }

    /**
     * Perform a model insert operation.
	 * 执行模型插入操作
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return bool
     */
    protected function performInsert(Builder $query)
    {
        if ($this->fireModelEvent('creating') === false) {
            return false;
        }

        // First we'll need to create a fresh query instance and touch the creation and
        // update timestamps on this model, which are maintained by us for developer
        // convenience. After, we will just continue saving these model instances.
		// 首先，我们需要创建一个新的查询实例，并触摸此模型上的创建和更新时间戳，
		// 这些时间戳由我们维护，以方便开发人员。之后，我们将继续保存这些模型实例。
        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
        }

        // If the model has an incrementing key, we can use the "insertGetId" method on
        // the query builder, which will give us back the final inserted ID for this
        // table from the database. Not all tables have to be incrementing though.
		// 如果模型具有递增键，我们可以在查询构建器上使用"insertGetId"方法，
		// 该方法将从数据库中返回此表的最终插入ID。不过，并非所有表都必须递增。
        $attributes = $this->getAttributes();

        if ($this->getIncrementing()) {
            $this->insertAndSetId($query, $attributes);
        }

        // If the table isn't incrementing we'll simply insert these attributes as they
        // are. These attribute arrays must contain an "id" column previously placed
        // there by the developer as the manually determined key for these models.
		// 如果表没有递增，我们将直接插入这些属性。这些属性数组必须包含一个"id"列，
		// 该列之前由开发人员放置在那里，作为这些模型的手动确定键。
        else {
            if (empty($attributes)) {
                return true;
            }

            $query->insert($attributes);
        }

        // We will go ahead and set the exists property to true, so that it is set when
        // the created event is fired, just in case the developer tries to update it
        // during the event. This will allow them to do so and run an update here.
		// 我们将继续将exists属性设置为true，以便在触发创建的事件时设置它，
		// 以防开发人员在事件期间试图更新它。这将允许他们这样做并在此处运行更新。
        $this->exists = true;

        $this->wasRecentlyCreated = true;

        $this->fireModelEvent('created', false);

        return true;
    }

    /**
     * Insert the given attributes and set the ID on the model.
	 * 插入给定的属性并在模型上设置ID
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array  $attributes
     * @return void
     */
    protected function insertAndSetId(Builder $query, $attributes)
    {
        $id = $query->insertGetId($attributes, $keyName = $this->getKeyName());

        $this->setAttribute($keyName, $id);
    }

    /**
     * Destroy the models for the given IDs.
	 * 销毁给定IDs的模型
     *
     * @param  \Illuminate\Support\Collection|array|int  $ids
     * @return int
     */
    public static function destroy($ids)
    {
        // We'll initialize a count here so we will return the total number of deletes
        // for the operation. The developers can then check this number as a boolean
        // type value or get this total count of records deleted for logging, etc.
		// 我们将在此处初始化一个计数，以便返回该操作的删除总数。
		// 然后，开发人员可以将此数字作为布尔类型值进行检查，或者获取为记录而删除的记录总数等。
        $count = 0;

        if ($ids instanceof BaseCollection) {
            $ids = $ids->all();
        }

        $ids = is_array($ids) ? $ids : func_get_args();

        // We will actually pull the models from the database table and call delete on
        // each of them individually so that their events get fired properly with a
        // correct set of attributes in case the developers wants to check these.
		// 我们实际上会从数据库表中提取模型，并分别对每个模型调用delete，
		// 以便在开发人员想要检查的情况下，使用正确的属性集正确触发它们的事件。
        $key = ($instance = new static)->getKeyName();

        foreach ($instance->whereIn($key, $ids)->get() as $model) {
            if ($model->delete()) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Delete the model from the database.
	 * 删除模型从数据库中
     *
     * @return bool|null
     *
     * @throws \Exception
     */
    public function delete()
    {
        if (is_null($this->getKeyName())) {
            throw new Exception('No primary key defined on model.');
        }

        // If the model doesn't exist, there is nothing to delete so we'll just return
        // immediately and not do anything else. Otherwise, we will continue with a
        // deletion process on the model, firing the proper events, and so forth.
		// 如果模型不存在，则没有什么可删除的，所以我们将立即返回，不做任何其他事情。
		// 否则，我们将继续对模型进行删除过程，触发适当的事件，等等。
        if (! $this->exists) {
            return;
        }

        if ($this->fireModelEvent('deleting') === false) {
            return false;
        }

        // Here, we'll touch the owning models, verifying these timestamps get updated
        // for the models. This will allow any caching to get broken on the parents
        // by the timestamp. Then we will go ahead and delete the model instance.
		// 在这里，我们将触摸所拥有的模型，验证这些时间戳是否为模型更新。
		// 这将允许任何缓存在父节点上被时间戳破坏。然后，我们将继续删除模型实例。
        $this->touchOwners();

        $this->performDeleteOnModel();

        // Once the model has been deleted, we will fire off the deleted event so that
        // the developers may hook into post-delete operations. We will then return
        // a boolean true as the delete is presumably successful on the database.
		// 一旦模型被删除，我们将触发删除事件，这样私奔者就可以挂接到删除后的操作中。
		// 然后，我们将返回一个布尔值true，因为数据库上的删除可能是成功的。
        $this->fireModelEvent('deleted', false);

        return true;
    }

    /**
     * Force a hard delete on a soft deleted model.
	 *强制执行硬删除对已软删除的模型
     *
     * This method protects developers from running forceDelete when trait is missing.
     *
     * @return bool|null
     */
    public function forceDelete()
    {
        return $this->delete();
    }

    /**
     * Perform the actual delete query on this model instance.
	 * 执行实际的删除查询对这个模型实例
     *
     * @return void
     */
    protected function performDeleteOnModel()
    {
        $this->setKeysForSaveQuery($this->newModelQuery())->delete();

        $this->exists = false;
    }

    /**
     * Begin querying the model.
	 * 开始查询模型
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function query()
    {
        return (new static)->newQuery();
    }

    /**
     * Get a new query builder for the model's table.
	 * 得到模型表的新查询生成器
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newQuery()
    {
        return $this->registerGlobalScopes($this->newQueryWithoutScopes());
    }

    /**
     * Get a new query builder that doesn't have any global scopes or eager loading.
	 * 得到一个没有任何全局作用域或主动加载的新查询生成器
     *
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function newModelQuery()
    {
        return $this->newEloquentBuilder(
            $this->newBaseQueryBuilder()
        )->setModel($this);
    }

    /**
     * Get a new query builder with no relationships loaded.
	 * 得到没有加载任何关系的新查询生成器
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newQueryWithoutRelationships()
    {
        return $this->registerGlobalScopes($this->newModelQuery());
    }

    /**
     * Register the global scopes for this builder instance.
	 * 注册全局范围为此构建器实例
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function registerGlobalScopes($builder)
    {
        foreach ($this->getGlobalScopes() as $identifier => $scope) {
            $builder->withGlobalScope($identifier, $scope);
        }

        return $builder;
    }

    /**
     * Get a new query builder that doesn't have any global scopes.
	 * 得到一个没有任何全局作用域的新查询生成器
     *
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function newQueryWithoutScopes()
    {
        return $this->newModelQuery()
                    ->with($this->with)
                    ->withCount($this->withCount);
    }

    /**
     * Get a new query instance without a given scope.
	 * 得到没有给定范围的新查询实例
     *
     * @param  \Illuminate\Database\Eloquent\Scope|string  $scope
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newQueryWithoutScope($scope)
    {
        return $this->newQuery()->withoutGlobalScope($scope);
    }

    /**
     * Get a new query to restore one or more models by their queueable IDs.
	 * 得到一个新查询，根据可排队IDS还原一个或多个模型。
     *
     * @param  array|int  $ids
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newQueryForRestoration($ids)
    {
        return is_array($ids)
                ? $this->newQueryWithoutScopes()->whereIn($this->getQualifiedKeyName(), $ids)
                : $this->newQueryWithoutScopes()->whereKey($ids);
    }

    /**
     * Create a new Eloquent query builder for the model.
	 * 创建一个新的Eloquent查询构建器为模型
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    /**
     * Get a new query builder instance for the connection.
	 * 得到连接的新查询生成器实例
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function newBaseQueryBuilder()
    {
        return $this->getConnection()->query();
    }

    /**
     * Create a new Eloquent Collection instance.
	 * 创建新的Eloquent Collection实例
     *
     * @param  array  $models
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function newCollection(array $models = [])
    {
        return new Collection($models);
    }

    /**
     * Create a new pivot model instance.
	 * 创建新的pivot模型实例
     *
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  array  $attributes
     * @param  string  $table
     * @param  bool  $exists
     * @param  string|null  $using
     * @return \Illuminate\Database\Eloquent\Relations\Pivot
     */
    public function newPivot(self $parent, array $attributes, $table, $exists, $using = null)
    {
        return $using ? $using::fromRawAttributes($parent, $attributes, $table, $exists)
                      : Pivot::fromAttributes($parent, $attributes, $table, $exists);
    }

    /**
     * Convert the model instance to an array.
	 * 转换模型实例为数组
     *
     * @return array
     */
    public function toArray()
    {
        return array_merge($this->attributesToArray(), $this->relationsToArray());
    }

    /**
     * Convert the model instance to JSON.
	 * 转换模型实例为JSON
     *
     * @param  int  $options
     * @return string
     *
     * @throws \Illuminate\Database\Eloquent\JsonEncodingException
     */
    public function toJson($options = 0)
    {
        $json = json_encode($this->jsonSerialize(), $options);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw JsonEncodingException::forModel($this, json_last_error_msg());
        }

        return $json;
    }

    /**
     * Convert the object into something JSON serializable.
	 * 转换对象为JSON可序列化的对象
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Reload a fresh model instance from the database.
	 * 重新加载一个新的模型实例从数据库中
     *
     * @param  array|string  $with
     * @return static|null
     */
    public function fresh($with = [])
    {
        if (! $this->exists) {
            return;
        }

        return static::newQueryWithoutScopes()
                        ->with(is_string($with) ? func_get_args() : $with)
                        ->where($this->getKeyName(), $this->getKey())
                        ->first();
    }

    /**
     * Reload the current model instance with fresh attributes from the database.
	 * 用数据库中的新属性重新加载当前模型实例。
     *
     * @return $this
     */
    public function refresh()
    {
        if (! $this->exists) {
            return $this;
        }

        $this->setRawAttributes(
            static::newQueryWithoutScopes()->findOrFail($this->getKey())->attributes
        );

        $this->load(collect($this->relations)->reject(function ($relation) {
            return $relation instanceof Pivot
                || (is_object($relation) && in_array(AsPivot::class, class_uses_recursive($relation), true));
        })->keys()->all());

        $this->syncOriginal();

        return $this;
    }

    /**
     * Clone the model into a new, non-existing instance.
	 * 将模型克隆到一个新的，不存在的实例。
     *
     * @param  array|null  $except
     * @return static
     */
    public function replicate(array $except = null)
    {
        $defaults = [
            $this->getKeyName(),
            $this->getCreatedAtColumn(),
            $this->getUpdatedAtColumn(),
        ];

        $attributes = Arr::except(
            $this->attributes, $except ? array_unique(array_merge($except, $defaults)) : $defaults
        );

        return tap(new static, function ($instance) use ($attributes) {
            $instance->setRawAttributes($attributes);

            $instance->setRelations($this->relations);

            $instance->fireModelEvent('replicating', false);
        });
    }

    /**
     * Determine if two models have the same ID and belong to the same table.
	 * 确定两个模型是否具有相同的ID并属于同一表
     *
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     * @return bool
     */
    public function is($model)
    {
        return ! is_null($model) &&
               $this->getKey() === $model->getKey() &&
               $this->getTable() === $model->getTable() &&
               $this->getConnectionName() === $model->getConnectionName();
    }

    /**
     * Determine if two models are not the same.
	 * 确定两个模型是否不相同
     *
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     * @return bool
     */
    public function isNot($model)
    {
        return ! $this->is($model);
    }

    /**
     * Get the database connection for the model.
	 * 得到模型的数据库连接
     *
     * @return \Illuminate\Database\Connection
     */
    public function getConnection()
    {
        return static::resolveConnection($this->getConnectionName());
    }

    /**
     * Get the current connection name for the model.
	 * 得到模型的当前连接名称
     *
     * @return string|null
     */
    public function getConnectionName()
    {
        return $this->connection;
    }

    /**
     * Set the connection associated with the model.
	 * 设置与模型关联的连接
     *
     * @param  string|null  $name
     * @return $this
     */
    public function setConnection($name)
    {
        $this->connection = $name;

        return $this;
    }

    /**
     * Resolve a connection instance.
	 * 解析连接实例
     *
     * @param  string|null  $connection
     * @return \Illuminate\Database\Connection
     */
    public static function resolveConnection($connection = null)
    {
        return static::$resolver->connection($connection);
    }

    /**
     * Get the connection resolver instance.
	 * 得到连接解析器实例
     *
     * @return \Illuminate\Database\ConnectionResolverInterface
     */
    public static function getConnectionResolver()
    {
        return static::$resolver;
    }

    /**
     * Set the connection resolver instance.
	 * 设置连接解析器实例
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     * @return void
     */
    public static function setConnectionResolver(Resolver $resolver)
    {
        static::$resolver = $resolver;
    }

    /**
     * Unset the connection resolver for models.
	 * 取消设置模型的连接解析器
     *
     * @return void
     */
    public static function unsetConnectionResolver()
    {
        static::$resolver = null;
    }

    /**
     * Get the table associated with the model.
	 * 得到与模型相关联的表
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table ?? Str::snake(Str::pluralStudly(class_basename($this)));
    }

    /**
     * Set the table associated with the model.
	 * 设置与模型相关联的表
     *
     * @param  string  $table
     * @return $this
     */
    public function setTable($table)
    {
        $this->table = $table;

        return $this;
    }

    /**
     * Get the primary key for the model.
	 * 得到模型的主键
     *
     * @return string
     */
    public function getKeyName()
    {
        return $this->primaryKey;
    }

    /**
     * Set the primary key for the model.
	 * 设置模型的主键
     *
     * @param  string  $key
     * @return $this
     */
    public function setKeyName($key)
    {
        $this->primaryKey = $key;

        return $this;
    }

    /**
     * Get the table qualified key name.
	 * 得到表限定键名
     *
     * @return string
     */
    public function getQualifiedKeyName()
    {
        return $this->qualifyColumn($this->getKeyName());
    }

    /**
     * Get the auto-incrementing key type.
	 * 得到自动递增的键类型
     *
     * @return string
     */
    public function getKeyType()
    {
        return $this->keyType;
    }

    /**
     * Set the data type for the primary key.
	 * 设置主键的数据类型
     *
     * @param  string  $type
     * @return $this
     */
    public function setKeyType($type)
    {
        $this->keyType = $type;

        return $this;
    }

    /**
     * Get the value indicating whether the IDs are incrementing.
	 * 得到指示id是否在递增的值
     *
     * @return bool
     */
    public function getIncrementing()
    {
        return $this->incrementing;
    }

    /**
     * Set whether IDs are incrementing.
	 * 设置id是否递增
     *
     * @param  bool  $value
     * @return $this
     */
    public function setIncrementing($value)
    {
        $this->incrementing = $value;

        return $this;
    }

    /**
     * Get the value of the model's primary key.
	 * 得到模型主键的值
     *
     * @return mixed
     */
    public function getKey()
    {
        return $this->getAttribute($this->getKeyName());
    }

    /**
     * Get the queueable identity for the entity.
	 * 得到实体的可排队标识
     *
     * @return mixed
     */
    public function getQueueableId()
    {
        return $this->getKey();
    }

    /**
     * Get the queueable relationships for the entity.
	 * 得到实体的可排队关系
     *
     * @return array
     */
    public function getQueueableRelations()
    {
        $relations = [];

        foreach ($this->getRelations() as $key => $relation) {
            if (! method_exists($this, $key)) {
                continue;
            }

            $relations[] = $key;

            if ($relation instanceof QueueableCollection) {
                foreach ($relation->getQueueableRelations() as $collectionValue) {
                    $relations[] = $key.'.'.$collectionValue;
                }
            }

            if ($relation instanceof QueueableEntity) {
                foreach ($relation->getQueueableRelations() as $entityKey => $entityValue) {
                    $relations[] = $key.'.'.$entityValue;
                }
            }
        }

        return array_unique($relations);
    }

    /**
     * Get the queueable connection for the entity.
	 * 得到实体的可排队连接
     *
     * @return string|null
     */
    public function getQueueableConnection()
    {
        return $this->getConnectionName();
    }

    /**
     * Get the value of the model's route key.
	 * 得到模型的路由键值
     *
     * @return mixed
     */
    public function getRouteKey()
    {
        return $this->getAttribute($this->getRouteKeyName());
    }

    /**
     * Get the route key for the model.
	 * 得到模型的路由键
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return $this->getKeyName();
    }

    /**
     * Retrieve the model for a bound value.
	 * 检索绑定值的模型
     *
     * @param  mixed  $value
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function resolveRouteBinding($value)
    {
        return $this->where($this->getRouteKeyName(), $value)->first();
    }

    /**
     * Get the default foreign key name for the model.
	 * 得到模型的默认外键名
     *
     * @return string
     */
    public function getForeignKey()
    {
        return Str::snake(class_basename($this)).'_'.$this->getKeyName();
    }

    /**
     * Get the number of models to return per page.
	 * 得到每页返回的模型数量
     *
     * @return int
     */
    public function getPerPage()
    {
        return $this->perPage;
    }

    /**
     * Set the number of models to return per page.
	 * 设置每页返回的模型数量
     *
     * @param  int  $perPage
     * @return $this
     */
    public function setPerPage($perPage)
    {
        $this->perPage = $perPage;

        return $this;
    }

    /**
     * Dynamically retrieve attributes on the model.
	 * 动态检索模型上的属性
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Dynamically set attributes on the model.
	 * 动态设置模型上的属性
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Determine if the given attribute exists.
	 * 确定给定属性是否存在
     *
     * @param  mixed  $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return ! is_null($this->getAttribute($offset));
    }

    /**
     * Get the value for a given offset.
	 * 得到给定偏移量的值
     *
     * @param  mixed  $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->getAttribute($offset);
    }

    /**
     * Set the value for a given offset.
	 * 设置给定偏移量的值
     *
     * @param  mixed  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->setAttribute($offset, $value);
    }

    /**
     * Unset the value for a given offset.
	 * 注销给定偏移量的值
     *
     * @param  mixed  $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->attributes[$offset], $this->relations[$offset]);
    }

    /**
     * Determine if an attribute or relation exists on the model.
	 * 确定模型上是否存在属性或关系
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return $this->offsetExists($key);
    }

    /**
     * Unset an attribute on the model.
	 * 注销模型上的属性
     *
     * @param  string  $key
     * @return void
     */
    public function __unset($key)
    {
        $this->offsetUnset($key);
    }

    /**
     * Handle dynamic method calls into the model.
	 * 处理对模型的动态方法调用
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (in_array($method, ['increment', 'decrement'])) {
            return $this->$method(...$parameters);
        }

        return $this->forwardCallTo($this->newQuery(), $method, $parameters);
    }

    /**
     * Handle dynamic static method calls into the method.
	 * 处理对方法的动态静态方法调用
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        return (new static)->$method(...$parameters);
    }

    /**
     * Convert the model to its string representation.
	 * 转换模型为其字符串表示形式
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * When a model is being unserialized, check if it needs to be booted.
	 * 当一个模型被反序列化时，检查它是否需要被引导
     *
     * @return void
     */
    public function __wakeup()
    {
        $this->bootIfNotBooted();
    }
}
