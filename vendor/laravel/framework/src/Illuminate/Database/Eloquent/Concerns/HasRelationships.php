<?php
/**
 * 数据库，Eloquent有关系
 */

namespace Illuminate\Database\Eloquent\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait HasRelationships
{
    /**
     * The loaded relationships for the model.
	 * 为模型加载的关系
     *
     * @var array
     */
    protected $relations = [];

    /**
     * The relationships that should be touched on save.
	 * 应该触及的关系保存
     *
     * @var array
     */
    protected $touches = [];

    /**
     * The many to many relationship methods.
	 * 多对多关系方法
     *
     * @var array
     */
    public static $manyMethods = [
        'belongsToMany', 'morphToMany', 'morphedByMany',
    ];

    /**
     * Define a one-to-one relationship.
	 * 定义一对一关系
     *
     * @param  string  $related
     * @param  string|null  $foreignKey
     * @param  string|null  $localKey
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function hasOne($related, $foreignKey = null, $localKey = null)
    {
        $instance = $this->newRelatedInstance($related);

        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $localKey = $localKey ?: $this->getKeyName();

        return $this->newHasOne($instance->newQuery(), $this, $instance->getTable().'.'.$foreignKey, $localKey);
    }

    /**
     * Instantiate a new HasOne relationship.
	 * 实例化一个新的HasOne关系
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  string  $foreignKey
     * @param  string  $localKey
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    protected function newHasOne(Builder $query, Model $parent, $foreignKey, $localKey)
    {
        return new HasOne($query, $parent, $foreignKey, $localKey);
    }

    /**
     * Define a has-one-through relationship.
	 * 定义一个OneThrough关系
     *
     * @param  string  $related
     * @param  string  $through
     * @param  string|null  $firstKey
     * @param  string|null  $secondKey
     * @param  string|null  $localKey
     * @param  string|null  $secondLocalKey
     * @return \Illuminate\Database\Eloquent\Relations\HasOneThrough
     */
    public function hasOneThrough($related, $through, $firstKey = null, $secondKey = null, $localKey = null, $secondLocalKey = null)
    {
        $through = new $through;

        $firstKey = $firstKey ?: $this->getForeignKey();

        $secondKey = $secondKey ?: $through->getForeignKey();

        return $this->newHasOneThrough(
            $this->newRelatedInstance($related)->newQuery(), $this, $through,
            $firstKey, $secondKey, $localKey ?: $this->getKeyName(),
            $secondLocalKey ?: $through->getKeyName()
        );
    }

    /**
     * Instantiate a new HasOneThrough relationship.
	 * 实例化一个新的HasOneThrough关系
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $farParent
     * @param  \Illuminate\Database\Eloquent\Model  $throughParent
     * @param  string  $firstKey
     * @param  string  $secondKey
     * @param  string  $localKey
     * @param  string  $secondLocalKey
     * @return \Illuminate\Database\Eloquent\Relations\HasOneThrough
     */
    protected function newHasOneThrough(Builder $query, Model $farParent, Model $throughParent, $firstKey, $secondKey, $localKey, $secondLocalKey)
    {
        return new HasOneThrough($query, $farParent, $throughParent, $firstKey, $secondKey, $localKey, $secondLocalKey);
    }

    /**
     * Define a polymorphic one-to-one relationship.
	 * 定义一个多态的一对一关系
     *
     * @param  string  $related
     * @param  string  $name
     * @param  string|null  $type
     * @param  string|null  $id
     * @param  string|null  $localKey
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function morphOne($related, $name, $type = null, $id = null, $localKey = null)
    {
        $instance = $this->newRelatedInstance($related);

        [$type, $id] = $this->getMorphs($name, $type, $id);

        $table = $instance->getTable();

        $localKey = $localKey ?: $this->getKeyName();

        return $this->newMorphOne($instance->newQuery(), $this, $table.'.'.$type, $table.'.'.$id, $localKey);
    }

    /**
     * Instantiate a new MorphOne relationship.
	 * 实例化一个新的MorphOne关系
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  string  $type
     * @param  string  $id
     * @param  string  $localKey
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    protected function newMorphOne(Builder $query, Model $parent, $type, $id, $localKey)
    {
        return new MorphOne($query, $parent, $type, $id, $localKey);
    }

    /**
     * Define an inverse one-to-one or many relationship.
	 * 定义一个逆的一对一或多关系
     *
     * @param  string  $related
     * @param  string|null  $foreignKey
     * @param  string|null  $ownerKey
     * @param  string|null  $relation
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function belongsTo($related, $foreignKey = null, $ownerKey = null, $relation = null)
    {
        // If no relation name was given, we will use this debug backtrace to extract
        // the calling method's name and use that as the relationship name as most
        // of the time this will be what we desire to use for the relationships.
		// 如果没有给出关系名称，我们将使用这个调试回溯来提取
		// 调用方法的名称，并将其用作关系名称，
		// 因为通常，这将是我们希望用于关系的东西。
        if (is_null($relation)) {
            $relation = $this->guessBelongsToRelation();
        }

        $instance = $this->newRelatedInstance($related);

        // If no foreign key was supplied, we can use a backtrace to guess the proper
        // foreign key name by using the name of the relationship function, which
        // when combined with an "_id" should conventionally match the columns.
		// 如果没有提供外键，我们可以使用回溯来猜测正确的外键使用关系函数的名称来命名，
		// 当与"_id"组合时，通常应该匹配列。
        if (is_null($foreignKey)) {
            $foreignKey = Str::snake($relation).'_'.$instance->getKeyName();
        }

        // Once we have the foreign key names, we'll just create a new Eloquent query
        // for the related models and returns the relationship instance which will
        // actually be responsible for retrieving and hydrating every relations.
		// 一旦有了外键名之后，我们将创建一个新的Eloquent查询获取相关模型，并返回关系实例，
		// 实际上负责恢复和滋润每一个关系。
        $ownerKey = $ownerKey ?: $instance->getKeyName();

        return $this->newBelongsTo(
            $instance->newQuery(), $this, $foreignKey, $ownerKey, $relation
        );
    }

    /**
     * Instantiate a new BelongsTo relationship.
	 * 实例化一个新的BelongsTo关系
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $child
     * @param  string  $foreignKey
     * @param  string  $ownerKey
     * @param  string  $relation
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    protected function newBelongsTo(Builder $query, Model $child, $foreignKey, $ownerKey, $relation)
    {
        return new BelongsTo($query, $child, $foreignKey, $ownerKey, $relation);
    }

    /**
     * Define a polymorphic, inverse one-to-one or many relationship.
	 * 定义一个多态的、反向的一对一或多关系。
     *
     * @param  string|null  $name
     * @param  string|null  $type
     * @param  string|null  $id
     * @param  string|null  $ownerKey
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function morphTo($name = null, $type = null, $id = null, $ownerKey = null)
    {
        // If no name is provided, we will use the backtrace to get the function name
        // since that is most likely the name of the polymorphic interface. We can
        // use that to get both the class and foreign key that will be utilized.
		// 如果没有提供名称，我们将使用回溯来获取函数名称，因为这很可能是多态接口的名称。
		// 我们能使用它来获取将要使用的类和外键。
        $name = $name ?: $this->guessBelongsToRelation();

        [$type, $id] = $this->getMorphs(
            Str::snake($name), $type, $id
        );

        // If the type value is null it is probably safe to assume we're eager loading
        // the relationship. In this case we'll just pass in a dummy query where we
        // need to remove any eager loads that may already be defined on a model.
		// 如果类型值为null，则可以安全地假设我们正在进行急于加载关系。
		// 在本例中，我们将传入一个虚拟查询需要删除任何可能已经在模型上定义的动态负载。
        return is_null($class = $this->{$type}) || $class === ''
                    ? $this->morphEagerTo($name, $type, $id, $ownerKey)
                    : $this->morphInstanceTo($class, $name, $type, $id, $ownerKey);
    }

    /**
     * Define a polymorphic, inverse one-to-one or many relationship.
	 * 定义一个多态的、反向的一对一或多关系
     *
     * @param  string  $name
     * @param  string  $type
     * @param  string  $id
     * @param  string  $ownerKey
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    protected function morphEagerTo($name, $type, $id, $ownerKey)
    {
        return $this->newMorphTo(
            $this->newQuery()->setEagerLoads([]), $this, $id, $ownerKey, $type, $name
        );
    }

    /**
     * Define a polymorphic, inverse one-to-one or many relationship.
	 * 定义一个多态的、反向的一对一或多关系
     *
     * @param  string  $target
     * @param  string  $name
     * @param  string  $type
     * @param  string  $id
     * @param  string  $ownerKey
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    protected function morphInstanceTo($target, $name, $type, $id, $ownerKey)
    {
        $instance = $this->newRelatedInstance(
            static::getActualClassNameForMorph($target)
        );

        return $this->newMorphTo(
            $instance->newQuery(), $this, $id, $ownerKey ?? $instance->getKeyName(), $type, $name
        );
    }

    /**
     * Instantiate a new MorphTo relationship.
	 * 实例化一个新的MorphTo关系
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  string  $foreignKey
     * @param  string  $ownerKey
     * @param  string  $type
     * @param  string  $relation
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    protected function newMorphTo(Builder $query, Model $parent, $foreignKey, $ownerKey, $type, $relation)
    {
        return new MorphTo($query, $parent, $foreignKey, $ownerKey, $type, $relation);
    }

    /**
     * Retrieve the actual class name for a given morph class.
	 * 检索给定变形类的实际类名
     *
     * @param  string  $class
     * @return string
     */
    public static function getActualClassNameForMorph($class)
    {
        return Arr::get(Relation::morphMap() ?: [], $class, $class);
    }

    /**
     * Guess the "belongs to" relationship name.
	 * 猜"属于"的关系名称
     *
     * @return string
     */
    protected function guessBelongsToRelation()
    {
        [$one, $two, $caller] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);

        return $caller['function'];
    }

    /**
     * Define a one-to-many relationship.
	 * 定义一对多关系
     *
     * @param  string  $related
     * @param  string|null  $foreignKey
     * @param  string|null  $localKey
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function hasMany($related, $foreignKey = null, $localKey = null)
    {
        $instance = $this->newRelatedInstance($related);

        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $localKey = $localKey ?: $this->getKeyName();

        return $this->newHasMany(
            $instance->newQuery(), $this, $instance->getTable().'.'.$foreignKey, $localKey
        );
    }

    /**
     * Instantiate a new HasMany relationship.
	 * 实例化一个新的HasMany关系
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  string  $foreignKey
     * @param  string  $localKey
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    protected function newHasMany(Builder $query, Model $parent, $foreignKey, $localKey)
    {
        return new HasMany($query, $parent, $foreignKey, $localKey);
    }

    /**
     * Define a has-many-through relationship.
	 * 定义一个has-many-through的关系
     *
     * @param  string  $related
     * @param  string  $through
     * @param  string|null  $firstKey
     * @param  string|null  $secondKey
     * @param  string|null  $localKey
     * @param  string|null  $secondLocalKey
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function hasManyThrough($related, $through, $firstKey = null, $secondKey = null, $localKey = null, $secondLocalKey = null)
    {
        $through = new $through;

        $firstKey = $firstKey ?: $this->getForeignKey();

        $secondKey = $secondKey ?: $through->getForeignKey();

        return $this->newHasManyThrough(
            $this->newRelatedInstance($related)->newQuery(), $this, $through,
            $firstKey, $secondKey, $localKey ?: $this->getKeyName(),
            $secondLocalKey ?: $through->getKeyName()
        );
    }

    /**
     * Instantiate a new HasManyThrough relationship.
	 * 实例化一个新的HasManyThrough关系
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $farParent
     * @param  \Illuminate\Database\Eloquent\Model  $throughParent
     * @param  string  $firstKey
     * @param  string  $secondKey
     * @param  string  $localKey
     * @param  string  $secondLocalKey
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    protected function newHasManyThrough(Builder $query, Model $farParent, Model $throughParent, $firstKey, $secondKey, $localKey, $secondLocalKey)
    {
        return new HasManyThrough($query, $farParent, $throughParent, $firstKey, $secondKey, $localKey, $secondLocalKey);
    }

    /**
     * Define a polymorphic one-to-many relationship.
	 * 定义一个多态的一对多关系
     *
     * @param  string  $related
     * @param  string  $name
     * @param  string|null  $type
     * @param  string|null  $id
     * @param  string|null  $localKey
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function morphMany($related, $name, $type = null, $id = null, $localKey = null)
    {
        $instance = $this->newRelatedInstance($related);

        // Here we will gather up the morph type and ID for the relationship so that we
        // can properly query the intermediate table of a relation. Finally, we will
        // get the table and create the relationship instances for the developers.
		// 这里我们将收集关系的变形类型和ID，以致于能正确查询关系的中间表。
		// 我们将获取表并为开发人员创建关系实例。
        [$type, $id] = $this->getMorphs($name, $type, $id);

        $table = $instance->getTable();

        $localKey = $localKey ?: $this->getKeyName();

        return $this->newMorphMany($instance->newQuery(), $this, $table.'.'.$type, $table.'.'.$id, $localKey);
    }

    /**
     * Instantiate a new MorphMany relationship.
	 * 实例化一个新的MorphMany关系
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  string  $type
     * @param  string  $id
     * @param  string  $localKey
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    protected function newMorphMany(Builder $query, Model $parent, $type, $id, $localKey)
    {
        return new MorphMany($query, $parent, $type, $id, $localKey);
    }

    /**
     * Define a many-to-many relationship.
	 * 定义多对多关系
     *
     * @param  string  $related
     * @param  string|null  $table
     * @param  string|null  $foreignPivotKey
     * @param  string|null  $relatedPivotKey
     * @param  string|null  $parentKey
     * @param  string|null  $relatedKey
     * @param  string|null  $relation
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function belongsToMany($related, $table = null, $foreignPivotKey = null, $relatedPivotKey = null,
                                  $parentKey = null, $relatedKey = null, $relation = null)
    {
        // If no relationship name was passed, we will pull backtraces to get the
        // name of the calling function. We will use that function name as the
        // title of this relation since that is a great convention to apply.
		// 如果没有传递关系名称，我们将拉回追溯以获取调用函数的名称。
		// 我们将使用该函数名作为这个关系的标题，因为这是一个伟大的惯例应用。
        if (is_null($relation)) {
            $relation = $this->guessBelongsToManyRelation();
        }

        // First, we'll need to determine the foreign key and "other key" for the
        // relationship. Once we have determined the keys we'll make the query
        // instances as well as the relationship instances we need for this.
		// 首先，我们需要定义外键和"其他键"的关系。
		// 否则，我们已经确定了要进行查询的键实例，以及我们需要的关系实例。
        $instance = $this->newRelatedInstance($related);

        $foreignPivotKey = $foreignPivotKey ?: $this->getForeignKey();

        $relatedPivotKey = $relatedPivotKey ?: $instance->getForeignKey();

        // If no table name was provided, we can guess it by concatenating the two
        // models using underscores in alphabetical order. The two model names
        // are transformed to snake case from their default CamelCase also.
		// 如果没有提供表名，我们可以通过连接这两个表名来猜测使用下划线的模型。
		// 两个模型名也从默认的CamelCase转换为snake case。
        if (is_null($table)) {
            $table = $this->joiningTable($related, $instance);
        }

        return $this->newBelongsToMany(
            $instance->newQuery(), $this, $table, $foreignPivotKey,
            $relatedPivotKey, $parentKey ?: $this->getKeyName(),
            $relatedKey ?: $instance->getKeyName(), $relation
        );
    }

    /**
     * Instantiate a new BelongsToMany relationship.
	 * 实例化一个新的BelongsToMany关系
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  string  $table
     * @param  string  $foreignPivotKey
     * @param  string  $relatedPivotKey
     * @param  string  $parentKey
     * @param  string  $relatedKey
     * @param  string  $relationName
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    protected function newBelongsToMany(Builder $query, Model $parent, $table, $foreignPivotKey, $relatedPivotKey,
                                        $parentKey, $relatedKey, $relationName = null)
    {
        return new BelongsToMany($query, $parent, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relationName);
    }

    /**
     * Define a polymorphic many-to-many relationship.
	 * 定义多态多对多关系
     *
     * @param  string  $related
     * @param  string  $name
     * @param  string|null  $table
     * @param  string|null  $foreignPivotKey
     * @param  string|null  $relatedPivotKey
     * @param  string|null  $parentKey
     * @param  string|null  $relatedKey
     * @param  bool  $inverse
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function morphToMany($related, $name, $table = null, $foreignPivotKey = null,
                                $relatedPivotKey = null, $parentKey = null,
                                $relatedKey = null, $inverse = false)
    {
        $caller = $this->guessBelongsToManyRelation();

        // First, we will need to determine the foreign key and "other key" for the
        // relationship. Once we have determined the keys we will make the query
        // instances, as well as the relationship instances we need for these.
		// 首先，我们需要定义外键和"其他键"的关系。
		// 一旦我们确定了密钥，我们进行查询实例，以及我们需要的关系实例。
        $instance = $this->newRelatedInstance($related);

        $foreignPivotKey = $foreignPivotKey ?: $name.'_id';

        $relatedPivotKey = $relatedPivotKey ?: $instance->getForeignKey();

        // Now we're ready to create a new query builder for this related model and
        // the relationship instances for this relation. This relations will set
        // appropriate query constraints then entirely manages the hydrations.
		// 现在，我们准备为这个相关模型和此关系的关系实例创建一个新的查询构建器。
		// 这个关系将设置适当的查询约束完全管理水合物。
        if (! $table) {
            $words = preg_split('/(_)/u', $name, -1, PREG_SPLIT_DELIM_CAPTURE);

            $lastWord = array_pop($words);

            $table = implode('', $words).Str::plural($lastWord);
        }

        return $this->newMorphToMany(
            $instance->newQuery(), $this, $name, $table,
            $foreignPivotKey, $relatedPivotKey, $parentKey ?: $this->getKeyName(),
            $relatedKey ?: $instance->getKeyName(), $caller, $inverse
        );
    }

    /**
     * Instantiate a new MorphToMany relationship.
	 * 实例化一个新的morphmany关系
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  string  $name
     * @param  string  $table
     * @param  string  $foreignPivotKey
     * @param  string  $relatedPivotKey
     * @param  string  $parentKey
     * @param  string  $relatedKey
     * @param  string|null  $relationName
     * @param  bool  $inverse
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    protected function newMorphToMany(Builder $query, Model $parent, $name, $table, $foreignPivotKey,
                                      $relatedPivotKey, $parentKey, $relatedKey,
                                      $relationName = null, $inverse = false)
    {
        return new MorphToMany($query, $parent, $name, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey,
            $relationName, $inverse);
    }

    /**
     * Define a polymorphic, inverse many-to-many relationship.
	 * 定义一个多态的、反向的多对多关系。
     *
     * @param  string  $related
     * @param  string  $name
     * @param  string|null  $table
     * @param  string|null  $foreignPivotKey
     * @param  string|null  $relatedPivotKey
     * @param  string|null  $parentKey
     * @param  string|null  $relatedKey
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function morphedByMany($related, $name, $table = null, $foreignPivotKey = null,
                                  $relatedPivotKey = null, $parentKey = null, $relatedKey = null)
    {
        $foreignPivotKey = $foreignPivotKey ?: $this->getForeignKey();

        // For the inverse of the polymorphic many-to-many relations, we will change
        // the way we determine the foreign and other keys, as it is the opposite
        // of the morph-to-many method since we're figuring out these inverses.
		// 对于多态多对多关系的反数，我们将改变我们确定外键和其他键的方式，
		// 正如它是相反的因为我们在求这些逆矩阵。
        $relatedPivotKey = $relatedPivotKey ?: $name.'_id';

        return $this->morphToMany(
            $related, $name, $table, $foreignPivotKey,
            $relatedPivotKey, $parentKey, $relatedKey, true
        );
    }

    /**
     * Get the relationship name of the belongsToMany relationship.
	 * 得到belongsToMany关系的关系名称
     *
     * @return string|null
     */
    protected function guessBelongsToManyRelation()
    {
        $caller = Arr::first(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), function ($trace) {
            return ! in_array(
                $trace['function'],
                array_merge(static::$manyMethods, ['guessBelongsToManyRelation'])
            );
        });

        return ! is_null($caller) ? $caller['function'] : null;
    }

    /**
     * Get the joining table name for a many-to-many relation.
	 * 得到多对多关系的连接表名称
     *
     * @param  string  $related
     * @param  \Illuminate\Database\Eloquent\Model|null  $instance
     * @return string
     */
    public function joiningTable($related, $instance = null)
    {
        // The joining table name, by convention, is simply the snake cased models
        // sorted alphabetically and concatenated with an underscore, so we can
        // just sort the models and join them together to get the table name.
		// 按照惯例，连接表的名称就是蛇形外壳模型并使用下划线连接，
		// 因此我们只需对模型进行排序并将它们连接在一起以获得表名。
        $segments = [
            $instance ? $instance->joiningTableSegment()
                      : Str::snake(class_basename($related)),
            $this->joiningTableSegment(),
        ];

        // Now that we have the model names in an array we can just sort them and
        // use the implode function to join them together with an underscores,
        // which is typically used by convention within the database system.
		// 现在我们在数组中有了模型名称，我们可以对它们进行排序，
		// 用内爆函数用下划线把它们连在一起。
        sort($segments);

        return strtolower(implode('_', $segments));
    }

    /**
     * Get this model's half of the intermediate table name for belongsToMany relationships.
	 * 得到该模型的belongsToMany关系的中间表名的一半
     *
     * @return string
     */
    public function joiningTableSegment()
    {
        return Str::snake(class_basename($this));
    }

    /**
     * Determine if the model touches a given relation.
	 * 确定模型是否触及给定关系
     *
     * @param  string  $relation
     * @return bool
     */
    public function touches($relation)
    {
        return in_array($relation, $this->touches);
    }

    /**
     * Touch the owning relations of the model.
	 * 触摸模型的归属关系
     *
     * @return void
     */
    public function touchOwners()
    {
        foreach ($this->touches as $relation) {
            $this->$relation()->touch();

            if ($this->$relation instanceof self) {
                $this->$relation->fireModelEvent('saved', false);

                $this->$relation->touchOwners();
            } elseif ($this->$relation instanceof Collection) {
                $this->$relation->each->touchOwners();
            }
        }
    }

    /**
     * Get the polymorphic relationship columns.
	 * 得到多态关系列
     *
     * @param  string  $name
     * @param  string  $type
     * @param  string  $id
     * @return array
     */
    protected function getMorphs($name, $type, $id)
    {
        return [$type ?: $name.'_type', $id ?: $name.'_id'];
    }

    /**
     * Get the class name for polymorphic relations.
	 * 得到多态关系列
     *
     * @return string
     */
    public function getMorphClass()
    {
        $morphMap = Relation::morphMap();

        if (! empty($morphMap) && in_array(static::class, $morphMap)) {
            return array_search(static::class, $morphMap, true);
        }

        return static::class;
    }

    /**
     * Create a new model instance for a related model.
	 * 创建一个新的模型实例为相关模型
     *
     * @param  string  $class
     * @return mixed
     */
    protected function newRelatedInstance($class)
    {
        return tap(new $class, function ($instance) {
            if (! $instance->getConnectionName()) {
                $instance->setConnection($this->connection);
            }
        });
    }

    /**
     * Get all the loaded relations for the instance.
	 * 得到实例的所有加载关系
     *
     * @return array
     */
    public function getRelations()
    {
        return $this->relations;
    }

    /**
     * Get a specified relationship.
	 * 得到指定的关系
     *
     * @param  string  $relation
     * @return mixed
     */
    public function getRelation($relation)
    {
        return $this->relations[$relation];
    }

    /**
     * Determine if the given relation is loaded.
	 * 确定是否加载了给定的关系
     *
     * @param  string  $key
     * @return bool
     */
    public function relationLoaded($key)
    {
        return array_key_exists($key, $this->relations);
    }

    /**
     * Set the given relationship on the model.
	 * 设置给定的关系在模型上
     *
     * @param  string  $relation
     * @param  mixed  $value
     * @return $this
     */
    public function setRelation($relation, $value)
    {
        $this->relations[$relation] = $value;

        return $this;
    }

    /**
     * Unset a loaded relationship.
	 * 取消已加载关系
     *
     * @param  string  $relation
     * @return $this
     */
    public function unsetRelation($relation)
    {
        unset($this->relations[$relation]);

        return $this;
    }

    /**
     * Set the entire relations array on the model.
	 * 设置整个关系数组在模型上
     *
     * @param  array  $relations
     * @return $this
     */
    public function setRelations(array $relations)
    {
        $this->relations = $relations;

        return $this;
    }

    /**
     * Duplicate the instance and unset all the loaded relations.
	 * 复制实例并取消所有加载关系的设置
     *
     * @return $this
     */
    public function withoutRelations()
    {
        $model = clone $this;

        return $model->unsetRelations();
    }

    /**
     * Unset all the loaded relations for the instance.
	 * 取消为实例加载的所有关系的设置
     *
     * @return $this
     */
    public function unsetRelations()
    {
        $this->relations = [];

        return $this;
    }

    /**
     * Get the relationships that are touched on save.
	 * 得到保存时触及的关系
     *
     * @return array
     */
    public function getTouchedRelations()
    {
        return $this->touches;
    }

    /**
     * Set the relationships that are touched on save.
	 * 设置保存时触及的关系
     *
     * @param  array  $touches
     * @return $this
     */
    public function setTouchedRelations(array $touches)
    {
        $this->touches = $touches;

        return $this;
    }
}
