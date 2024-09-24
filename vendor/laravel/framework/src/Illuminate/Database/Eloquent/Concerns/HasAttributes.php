<?php
/**
 * 数据库，Eloquent有属性
 */

namespace Illuminate\Database\Eloquent\Concerns;

use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\JsonEncodingException;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use LogicException;

trait HasAttributes
{
    /**
     * The model's attributes.
	 * 模型属性
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * The model attribute's original state.
	 * 模型属性的原始状态
     *
     * @var array
     */
    protected $original = [];

    /**
     * The changed model attributes.
	 * 更改的模型属性
     *
     * @var array
     */
    protected $changes = [];

    /**
     * The attributes that should be cast to native types.
	 * 应该转换为本机类型的属性
     *
     * @var array
     */
    protected $casts = [];

    /**
     * The attributes that should be mutated to dates.
	 * 应该被更改为日期的属性
     *
     * @var array
     */
    protected $dates = [];

    /**
     * The storage format of the model's date columns.
	 * 模型日期列的存储格式
     *
     * @var string
     */
    protected $dateFormat;

    /**
     * The accessors to append to the model's array form.
	 * 附加到模型数组形式的访问器
     *
     * @var array
     */
    protected $appends = [];

    /**
     * Indicates whether attributes are snake cased on arrays.
	 * 指明属性是否在数组上使用蛇形大小写
     *
     * @var bool
     */
    public static $snakeAttributes = true;

    /**
     * The cache of the mutated attributes for each class.
	 * 每个类的突变属性的缓存
     *
     * @var array
     */
    protected static $mutatorCache = [];

    /**
     * Convert the model's attributes to an array.
	 * 转换模型的属性为数组
     *
     * @return array
     */
    public function attributesToArray()
    {
        // If an attribute is a date, we will cast it to a string after converting it
        // to a DateTime / Carbon instance. This is so we will get some consistent
        // formatting while accessing attributes vs. arraying / JSONing a model.
		// 如果属性为日期，我们将转换其强制转换为字符串。
		// 这样我们就能得到一致的结果格式。
        $attributes = $this->addDateAttributesToArray(
            $attributes = $this->getArrayableAttributes()
        );

        $attributes = $this->addMutatedAttributesToArray(
            $attributes, $mutatedAttributes = $this->getMutatedAttributes()
        );

        // Next we will handle any casts that have been setup for this model and cast
        // the values to their appropriate type. If the attribute has a mutator we
        // will not perform the cast on those attributes to avoid any confusion.
		// 接下来，我们将处理为此模型设置的任何转换，并将值转换为相应的类型。
		// 如果属性有一个变量，我们将不对这些属性执行强制转换，以避免任何混淆。
        $attributes = $this->addCastAttributesToArray(
            $attributes, $mutatedAttributes
        );

        // Here we will grab all of the appended, calculated attributes to this model
        // as these attributes are not really in the attributes array, but are run
        // when we need to array or JSON the model for convenience to the coder.
		// 在这里，我们将获取此模型的所有附加计算属性，
		// 为这些属性并不真正在attributes数组中，
		// 而是运行的当我们需要数组或JSON模型以方便编码时。
        foreach ($this->getArrayableAppends() as $key) {
            $attributes[$key] = $this->mutateAttributeForArray($key, null);
        }

        return $attributes;
    }

    /**
     * Add the date attributes to the attributes array.
	 * 添加日期属性到属性数组中
     *
     * @param  array  $attributes
     * @return array
     */
    protected function addDateAttributesToArray(array $attributes)
    {
        foreach ($this->getDates() as $key) {
            if (! isset($attributes[$key])) {
                continue;
            }

            $attributes[$key] = $this->serializeDate(
                $this->asDateTime($attributes[$key])
            );
        }

        return $attributes;
    }

    /**
     * Add the mutated attributes to the attributes array.
	 * 添加突变的属性到属性数组中
     *
     * @param  array  $attributes
     * @param  array  $mutatedAttributes
     * @return array
     */
    protected function addMutatedAttributesToArray(array $attributes, array $mutatedAttributes)
    {
        foreach ($mutatedAttributes as $key) {
            // We want to spin through all the mutated attributes for this model and call
            // the mutator for the attribute. We cache off every mutated attributes so
            // we don't have to constantly check on attributes that actually change.
			// 我们想浏览这个模型的所有突变属性，并调用增变对于属性。
			// 我们缓存了所有变异的属性，这样我们不必经常检查实际发生变化的属性。
            if (! array_key_exists($key, $attributes)) {
                continue;
            }

            // Next, we will call the mutator for this attribute so that we can get these
            // mutated attribute's actual values. After we finish mutating each of the
            // attributes we will return this final array of the mutated attributes.
			// 接下来，我们将调用此属性的增变，以便我们可以得到这些变异属性的实际值。
			// 在我们完成每个基因的变异之后，我们将返回这个最终的变异属性数组。
            $attributes[$key] = $this->mutateAttributeForArray(
                $key, $attributes[$key]
            );
        }

        return $attributes;
    }

    /**
     * Add the casted attributes to the attributes array.
	 * 转添加换属性到属性数组中
     *
     * @param  array  $attributes
     * @param  array  $mutatedAttributes
     * @return array
     */
    protected function addCastAttributesToArray(array $attributes, array $mutatedAttributes)
    {
        foreach ($this->getCasts() as $key => $value) {
            if (! array_key_exists($key, $attributes) || in_array($key, $mutatedAttributes)) {
                continue;
            }

            // Here we will cast the attribute. Then, if the cast is a date or datetime cast
            // then we will serialize the date for the array. This will convert the dates
            // to strings based on the date format specified for these Eloquent models.
			// 在这里，我们将投射属性。如果转换是日期或日期时间，然后我们将序列化数组的日期。
			// 这将转换日期根据为这些Eloquent模型指定的日期格式转换为字符串。
            $attributes[$key] = $this->castAttribute(
                $key, $attributes[$key]
            );

            // If the attribute cast was a date or a datetime, we will serialize the date as
            // a string. This allows the developers to customize how dates are serialized
            // into an array without affecting how they are persisted into the storage.
			// 如果属性强制转换是日期或日期时间，则将日期序列化为字符串。
			// 这允许开发人员自定义日期序列化的方式到数组中，而不影响它们持久化到存储中的方式。
            if ($attributes[$key] &&
                ($value === 'date' || $value === 'datetime')) {
                $attributes[$key] = $this->serializeDate($attributes[$key]);
            }

            if ($attributes[$key] && $this->isCustomDateTimeCast($value)) {
                $attributes[$key] = $attributes[$key]->format(explode(':', $value, 2)[1]);
            }

            if ($attributes[$key] instanceof Arrayable) {
                $attributes[$key] = $attributes[$key]->toArray();
            }
        }

        return $attributes;
    }

    /**
     * Get an attribute array of all arrayable attributes.
	 * 得到包含所有可数组属性的属性数组
     *
     * @return array
     */
    protected function getArrayableAttributes()
    {
        return $this->getArrayableItems($this->attributes);
    }

    /**
     * Get all of the appendable values that are arrayable.
	 * 得到所有可数组的可追加值
     *
     * @return array
     */
    protected function getArrayableAppends()
    {
        if (! count($this->appends)) {
            return [];
        }

        return $this->getArrayableItems(
            array_combine($this->appends, $this->appends)
        );
    }

    /**
     * Get the model's relationships in array form.
	 * 得到模型的关系以数组形式
     *
     * @return array
     */
    public function relationsToArray()
    {
        $attributes = [];

        foreach ($this->getArrayableRelations() as $key => $value) {
            // If the values implements the Arrayable interface we can just call this
            // toArray method on the instances which will convert both models and
            // collections to their proper array form and we'll set the values.
			// 如果value实现了Arrayable接口，我们可以调用这个实例上的toArray方法
			// 将转换模型和集合转换为正确的数组形式，我们将设置值。
            if ($value instanceof Arrayable) {
                $relation = $value->toArray();
            }

            // If the value is null, we'll still go ahead and set it in this list of
            // attributes since null is used to represent empty relationships if
            // if it a has one or belongs to type relationships on the models.
			// 如果值为空，我们仍将继续在这个列表中设置属性，因为null用于表示空关系，
			// 如果它有一个或属于模型上的类型关系。
            elseif (is_null($value)) {
                $relation = $value;
            }

            // If the relationships snake-casing is enabled, we will snake case this
            // key so that the relation attribute is snake cased in this returned
            // array to the developers, making this consistent with attributes.
			// 如果启用了关系蛇形封装，我们将对其进行蛇形封装，
			// 以便对开发者关系属性在这个返回中是蛇形的，使其与属性一致。
            if (static::$snakeAttributes) {
                $key = Str::snake($key);
            }

            // If the relation value has been set, we will set it on this attributes
            // list for returning. If it was not arrayable or null, we'll not set
            // the value on the array because it is some type of invalid value.
			// 如果已经设置了关系值，我们将在此属性上设置返回清单。
			// 如果它不是可数组的或者是空的，我们不会设置数组上的值，因为它是某种类型的无效值。
            if (isset($relation) || is_null($value)) {
                $attributes[$key] = $relation;
            }

            unset($relation);
        }

        return $attributes;
    }

    /**
     * Get an attribute array of all arrayable relations.
	 * 得到所有可数组关系的属性数组
     *
     * @return array
     */
    protected function getArrayableRelations()
    {
        return $this->getArrayableItems($this->relations);
    }

    /**
     * Get an attribute array of all arrayable values.
	 * 得到所有可数组值的属性数组
     *
     * @param  array  $values
     * @return array
     */
    protected function getArrayableItems(array $values)
    {
        if (count($this->getVisible()) > 0) {
            $values = array_intersect_key($values, array_flip($this->getVisible()));
        }

        if (count($this->getHidden()) > 0) {
            $values = array_diff_key($values, array_flip($this->getHidden()));
        }

        return $values;
    }

    /**
     * Get an attribute from the model.
	 * 得到一个属性从模型中
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        if (! $key) {
            return;
        }

        // If the attribute exists in the attribute array or has a "get" mutator we will
        // get the attribute's value. Otherwise, we will proceed as if the developers
        // are asking for a relationship's value. This covers both types of values.
		// 如果属性存在于属性数组中或具有“get”变量，我们将获取属性的值。
		// 否则，我们将像开发商一样继续进行他们要求一段关系的价值。这涵盖了这两种类型的值。
        if (array_key_exists($key, $this->attributes) ||
            $this->hasGetMutator($key)) {
            return $this->getAttributeValue($key);
        }

        // Here we will determine if the model base class itself contains this given key
        // since we don't want to treat any of those methods as relationships because
        // they are all intended as helper methods and none of these are relations.
		// 在这里，我们将确定模型基类本身是否包含此给定的键，
		// 因为我们不想将这些方法中的任何一个视为关系，因为它们都是辅助方法，
		// 而这些方法都不是关系。
        if (method_exists(self::class, $key)) {
            return;
        }

        return $this->getRelationValue($key);
    }

    /**
     * Get a plain attribute (not a relationship).
	 * 得到普通属性(而不是关系)
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttributeValue($key)
    {
        $value = $this->getAttributeFromArray($key);

        // If the attribute has a get mutator, we will call that then return what
        // it returns as the value, which is useful for transforming values on
        // retrieval from the model to a form that is more useful for usage.
		// 如果属性有一个get变量，我们将调用它，然后它以值的形式返回，
		// 这对于转换上的值很有用，从模型检索到对使用更有用的表单。
        if ($this->hasGetMutator($key)) {
            return $this->mutateAttribute($key, $value);
        }

        // If the attribute exists within the cast array, we will convert it to
        // an appropriate native PHP type dependent upon the associated value
        // given with the key in the pair. Dayle made this comment line up.
		// 如果该属性存在于强制转换数组中，我们将把它转换为取决于相关值的适当的本机PHP类型。
        if ($this->hasCast($key)) {
            return $this->castAttribute($key, $value);
        }

        // If the attribute is listed as a date, we will convert it to a DateTime
        // instance on retrieval, which makes it quite convenient to work with
        // date fields without having to create a mutator for each property.
		// 如果属性被列为日期，我们将把它转换为DateTime检索实例，
		// 使用起来非常方便日期字段，而无需为每个属性创建变量。
        if (in_array($key, $this->getDates()) &&
            ! is_null($value)) {
            return $this->asDateTime($value);
        }

        return $value;
    }

    /**
     * Get an attribute from the $attributes array.
	 * 得到一个属性从属性数组
     *
     * @param  string  $key
     * @return mixed
     */
    protected function getAttributeFromArray($key)
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Get a relationship.
	 * 得到关联关系
     *
     * @param  string  $key
     * @return mixed
     */
    public function getRelationValue($key)
    {
        // If the key already exists in the relationships array, it just means the
        // relationship has already been loaded, so we'll just return it out of
        // here because there is no need to query within the relations twice.
		// 如果主键在关系中存在，这意味着关联关系已被加载，
		// 因此我们将返回它，因为不需要在关系中查询两次。
        if ($this->relationLoaded($key)) {
            return $this->relations[$key];
        }

        // If the "attribute" exists as a method on the model, we will just assume
        // it is a relationship and will load and return results from the query
        // and hydrate the relationship's value on the "relationships" array.
		// 如果"属性"作为一个模型方法存在，我们将假定它是一个关联并加载并返回查询的结果，
		// 并且巩固"关系"数组中关系的值。
        if (method_exists($this, $key)) {
            return $this->getRelationshipFromMethod($key);
        }
    }

    /**
     * Get a relationship value from a method.
	 * 得到关系值从方法
     *
     * @param  string  $method
     * @return mixed
     *
     * @throws \LogicException
     */
    protected function getRelationshipFromMethod($method)
    {
        $relation = $this->$method();

        if (! $relation instanceof Relation) {
            if (is_null($relation)) {
                throw new LogicException(sprintf(
                    '%s::%s must return a relationship instance, but "null" was returned. Was the "return" keyword used?', static::class, $method
                ));
            }

            throw new LogicException(sprintf(
                '%s::%s must return a relationship instance.', static::class, $method
            ));
        }

        return tap($relation->getResults(), function ($results) use ($method) {
            $this->setRelation($method, $results);
        });
    }

    /**
     * Determine if a get mutator exists for an attribute.
	 * 确定属性是否存在得到变化
     *
     * @param  string  $key
     * @return bool
     */
    public function hasGetMutator($key)
    {
        return method_exists($this, 'get'.Str::studly($key).'Attribute');
    }

    /**
     * Get the value of an attribute using its mutator.
	 * 得到属性的值使用属性的赋值器
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function mutateAttribute($key, $value)
    {
        return $this->{'get'.Str::studly($key).'Attribute'}($value);
    }

    /**
     * Get the value of an attribute using its mutator for array conversion.
	 * 得到属性值使用属性的赋值器，以便进行数组转换。
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function mutateAttributeForArray($key, $value)
    {
        $value = $this->mutateAttribute($key, $value);

        return $value instanceof Arrayable ? $value->toArray() : $value;
    }

    /**
     * Cast an attribute to a native PHP type.
	 * 转换属性为本机PHP类型
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function castAttribute($key, $value)
    {
        if (is_null($value)) {
            return $value;
        }

        switch ($this->getCastType($key)) {
            case 'int':
            case 'integer':
                return (int) $value;
            case 'real':
            case 'float':
            case 'double':
                return $this->fromFloat($value);
            case 'decimal':
                return $this->asDecimal($value, explode(':', $this->getCasts()[$key], 2)[1]);
            case 'string':
                return (string) $value;
            case 'bool':
            case 'boolean':
                return (bool) $value;
            case 'object':
                return $this->fromJson($value, true);
            case 'array':
            case 'json':
                return $this->fromJson($value);
            case 'collection':
                return new BaseCollection($this->fromJson($value));
            case 'date':
                return $this->asDate($value);
            case 'datetime':
            case 'custom_datetime':
                return $this->asDateTime($value);
            case 'timestamp':
                return $this->asTimestamp($value);
            default:
                return $value;
        }
    }

    /**
     * Get the type of cast for a model attribute.
	 * 得到模型属性的强制转换类型
     *
     * @param  string  $key
     * @return string
     */
    protected function getCastType($key)
    {
        if ($this->isCustomDateTimeCast($this->getCasts()[$key])) {
            return 'custom_datetime';
        }

        if ($this->isDecimalCast($this->getCasts()[$key])) {
            return 'decimal';
        }

        return trim(strtolower($this->getCasts()[$key]));
    }

    /**
     * Determine if the cast type is a custom date time cast.
	 * 确定转换类型是否为自定义日期时间转换
     *
     * @param  string  $cast
     * @return bool
     */
    protected function isCustomDateTimeCast($cast)
    {
        return strncmp($cast, 'date:', 5) === 0 ||
               strncmp($cast, 'datetime:', 9) === 0;
    }

    /**
     * Determine if the cast type is a decimal cast.
	 * 确定转换类型是否为小数类型转换
     *
     * @param  string  $cast
     * @return bool
     */
    protected function isDecimalCast($cast)
    {
        return strncmp($cast, 'decimal:', 8) === 0;
    }

    /**
     * Set a given attribute on the model.
	 * 设置给定的属性在模型上
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    public function setAttribute($key, $value)
    {
        // First we will check for the presence of a mutator for the set operation
        // which simply lets the developers tweak the attribute as it is set on
        // the model, such as "json_encoding" an listing of data for storage.
		// 首先，我们将检查set操作中是否存在变异，
		// 这只是让开发人员在设置属性时对其进行调整该模型，
		// 如"jsonencoding"，用于存储数据列表。
        if ($this->hasSetMutator($key)) {
            return $this->setMutatedAttributeValue($key, $value);
        }

        // If an attribute is listed as a "date", we'll convert it from a DateTime
        // instance into a form proper for storage on the database tables using
        // the connection grammar's date format. We will auto set the values.
		// 如果一个属性被列为"日期"，我们将把它从DateTime转换为
		// 使用以下命令将实例转换为适合存储在数据库表上的形式连接语法的日期格式。
        elseif ($value && $this->isDateAttribute($key)) {
            $value = $this->fromDateTime($value);
        }

        if ($this->isJsonCastable($key) && ! is_null($value)) {
            $value = $this->castAttributeAsJson($key, $value);
        }

        // If this attribute contains a JSON ->, we'll set the proper value in the
        // attribute's underlying array. This takes care of properly nesting an
        // attribute in the array's value in the case of deeply nested items.
		// 如果此属性包含JSON->，我们将设置合适的值在属性底层数组。
		// 这可以在嵌套项较深的情况下，在数组的值中正确嵌套属性。
        if (Str::contains($key, '->')) {
            return $this->fillJsonAttribute($key, $value);
        }

        $this->attributes[$key] = $value;

        return $this;
    }

    /**
     * Determine if a set mutator exists for an attribute.
	 * 确定是否存在属性的集合赋值器
     *
     * @param  string  $key
     * @return bool
     */
    public function hasSetMutator($key)
    {
        return method_exists($this, 'set'.Str::studly($key).'Attribute');
    }

    /**
     * Set the value of an attribute using its mutator.
	 * 使用属性的赋值器设置属性的值
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function setMutatedAttributeValue($key, $value)
    {
        return $this->{'set'.Str::studly($key).'Attribute'}($value);
    }

    /**
     * Determine if the given attribute is a date or date castable.
	 * 确定给定的属性是日期还是日期可塑的
     *
     * @param  string  $key
     * @return bool
     */
    protected function isDateAttribute($key)
    {
        return in_array($key, $this->getDates(), true) ||
                                    $this->isDateCastable($key);
    }

    /**
     * Set a given JSON attribute on the model.
	 * 设置一个给定的JSON属性在模型上
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function fillJsonAttribute($key, $value)
    {
        [$key, $path] = explode('->', $key, 2);

        $this->attributes[$key] = $this->asJson($this->getArrayAttributeWithValue(
            $path, $key, $value
        ));

        return $this;
    }

    /**
     * Get an array attribute with the given key and value set.
	 * 得到具有给定键和值集的数组属性
     *
     * @param  string  $path
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    protected function getArrayAttributeWithValue($path, $key, $value)
    {
        return tap($this->getArrayAttributeByKey($key), function (&$array) use ($path, $value) {
            Arr::set($array, str_replace('->', '.', $path), $value);
        });
    }

    /**
     * Get an array attribute or return an empty array if it is not set.
	 * 得到数组属性，如果未设置则返回空数组。
     *
     * @param  string  $key
     * @return array
     */
    protected function getArrayAttributeByKey($key)
    {
        return isset($this->attributes[$key]) ?
                    $this->fromJson($this->attributes[$key]) : [];
    }

    /**
     * Cast the given attribute to JSON.
	 * 将给定的属性转换为JSON
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return string
     */
    protected function castAttributeAsJson($key, $value)
    {
        $value = $this->asJson($value);

        if ($value === false) {
            throw JsonEncodingException::forAttribute(
                $this, $key, json_last_error_msg()
            );
        }

        return $value;
    }

    /**
     * Encode the given value as JSON.
	 * 编码给定的值为JSON
     *
     * @param  mixed  $value
     * @return string
     */
    protected function asJson($value)
    {
        return json_encode($value);
    }

    /**
     * Decode the given JSON back into an array or object.
	 * 解码给定的JSON为数组或对象
     *
     * @param  string  $value
     * @param  bool  $asObject
     * @return mixed
     */
    public function fromJson($value, $asObject = false)
    {
        return json_decode($value, ! $asObject);
    }

    /**
     * Decode the given float.
	 * 解码给定的浮点数
     *
     * @param  mixed  $value
     * @return mixed
     */
    public function fromFloat($value)
    {
        switch ((string) $value) {
            case 'Infinity':
                return INF;
            case '-Infinity':
                return -INF;
            case 'NaN':
                return NAN;
            default:
                return (float) $value;
        }
    }

    /**
     * Return a decimal as string.
	 * 返回一个小数作为字符串
     *
     * @param  float  $value
     * @param  int  $decimals
     * @return string
     */
    protected function asDecimal($value, $decimals)
    {
        return number_format($value, $decimals, '.', '');
    }

    /**
     * Return a timestamp as DateTime object with time set to 00:00:00.
	 * 返回一个时间戳作为DateTime对象，时间设置为00:00:00。
     *
     * @param  mixed  $value
     * @return \Illuminate\Support\Carbon
     */
    protected function asDate($value)
    {
        return $this->asDateTime($value)->startOfDay();
    }

    /**
     * Return a timestamp as DateTime object.
	 * 返回一个时间戳作为DateTime对象
     *
     * @param  mixed  $value
     * @return \Illuminate\Support\Carbon
     */
    protected function asDateTime($value)
    {
        // If this value is already a Carbon instance, we shall just return it as is.
        // This prevents us having to re-instantiate a Carbon instance when we know
        // it already is one, which wouldn't be fulfilled by the DateTime check.
		// 如果此值已经是Carbon实例，我们将按原样返回它。	
		// 这可以防止我们在知道Carbon实例已经是一个实例的情况下重新实例化它，
		// 而DateTime检查不会满足这个要求。
        if ($value instanceof CarbonInterface) {
            return Date::instance($value);
        }

        // If the value is already a DateTime instance, we will just skip the rest of
        // these checks since they will be a waste of time, and hinder performance
        // when checking the field. We will just return the DateTime right away.
		// 如果该值已经是DateTime实例，我们将跳过其余的检查，
		// 因为它们将浪费时间，并在检查字段时阻碍性能。我们将立即返回DateTime。
        if ($value instanceof DateTimeInterface) {
            return Date::parse(
                $value->format('Y-m-d H:i:s.u'), $value->getTimezone()
            );
        }

        // If this value is an integer, we will assume it is a UNIX timestamp's value
        // and format a Carbon object from this timestamp. This allows flexibility
        // when defining your date fields as they might be UNIX timestamps here.
		// 如果此值是整数，我们将假设它是UNIX时间戳的值，并根据此时间戳格式化Carbon对象。
		// 这允许在定义日期字段时具有灵活性，因为它们可能是UNIX时间戳。
        if (is_numeric($value)) {
            return Date::createFromTimestamp($value);
        }

        // If the value is in simply year, month, day format, we will instantiate the
        // Carbon instances from that format. Again, this provides for simple date
        // fields on the database, while still supporting Carbonized conversion.
		// 如果该值是简单的年、月、日格式，我们将从该格式实例化Carbon实例。
		// 同样，这在数据库上提供了简单的日期字段，同时仍然支持碳化转换。
        if ($this->isStandardDateFormat($value)) {
            return Date::instance(Carbon::createFromFormat('Y-m-d', $value)->startOfDay());
        }

        $format = $this->getDateFormat();

        // https://bugs.php.net/bug.php?id=75577
        if (version_compare(PHP_VERSION, '7.3.0-dev', '<')) {
            $format = str_replace('.v', '.u', $format);
        }

        // Finally, we will just assume this date is in the format used by default on
        // the database connection and use that format to create the Carbon object
        // that is returned back out to the developers after we convert it here.
		// 最后，我们将假设此日期采用数据库连接上默认使用的格式，
		// 并使用该格式创建Carbon对象，在此处转换后将其返回给开发人员。
        return Date::createFromFormat($format, $value);
    }

    /**
     * Determine if the given value is a standard date format.
	 * 确定给定的值是否是标准日期格式
     *
     * @param  string  $value
     * @return bool
     */
    protected function isStandardDateFormat($value)
    {
        return preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value);
    }

    /**
     * Convert a DateTime to a storable string.
	 * 转换DateTime为可存储字符串
     *
     * @param  mixed  $value
     * @return string|null
     */
    public function fromDateTime($value)
    {
        return empty($value) ? $value : $this->asDateTime($value)->format(
            $this->getDateFormat()
        );
    }

    /**
     * Return a timestamp as unix timestamp.
	 * 返回时间戳为unix时间戳
     *
     * @param  mixed  $value
     * @return int
     */
    protected function asTimestamp($value)
    {
        return $this->asDateTime($value)->getTimestamp();
    }

    /**
     * Prepare a date for array / JSON serialization.
	 * 为数组/ JSON序列化准备一个日期
     *
     * @param  \DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format($this->getDateFormat());
    }

    /**
     * Get the attributes that should be converted to dates.
	 * 得到应转换为日期的属性
     *
     * @return array
     */
    public function getDates()
    {
        $defaults = [
            $this->getCreatedAtColumn(),
            $this->getUpdatedAtColumn(),
        ];

        return $this->usesTimestamps()
                    ? array_unique(array_merge($this->dates, $defaults))
                    : $this->dates;
    }

    /**
     * Get the format for database stored dates.
	 * 得到数据库存储日期的格式
     *
     * @return string
     */
    public function getDateFormat()
    {
        return $this->dateFormat ?: $this->getConnection()->getQueryGrammar()->getDateFormat();
    }

    /**
     * Set the date format used by the model.
	 * 设置模型使用的日期格式
     *
     * @param  string  $format
     * @return $this
     */
    public function setDateFormat($format)
    {
        $this->dateFormat = $format;

        return $this;
    }

    /**
     * Determine whether an attribute should be cast to a native type.
	 * 确定是否应将属性强制转换为本机类型
     *
     * @param  string  $key
     * @param  array|string|null  $types
     * @return bool
     */
    public function hasCast($key, $types = null)
    {
        if (array_key_exists($key, $this->getCasts())) {
            return $types ? in_array($this->getCastType($key), (array) $types, true) : true;
        }

        return false;
    }

    /**
     * Get the casts array.
	 * 强制类型转换数组
     *
     * @return array
     */
    public function getCasts()
    {
        if ($this->getIncrementing()) {
            return array_merge([$this->getKeyName() => $this->getKeyType()], $this->casts);
        }

        return $this->casts;
    }

    /**
     * Determine whether a value is Date / DateTime castable for inbound manipulation.
	 * 确定某个值是否可用于入站操作的日期时间
     *
     * @param  string  $key
     * @return bool
     */
    protected function isDateCastable($key)
    {
        return $this->hasCast($key, ['date', 'datetime']);
    }

    /**
     * Determine whether a value is JSON castable for inbound manipulation.
	 * 确定一个值是否可用于入站操作的JSON可塑
     *
     * @param  string  $key
     * @return bool
     */
    protected function isJsonCastable($key)
    {
        return $this->hasCast($key, ['array', 'json', 'object', 'collection']);
    }

    /**
     * Get all of the current attributes on the model.
	 * 得到模型上的所有当前属性
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Set the array of model attributes. No checking is done.
	 * 设置模型属性数组。没有检查。
     *
     * @param  array  $attributes
     * @param  bool  $sync
     * @return $this
     */
    public function setRawAttributes(array $attributes, $sync = false)
    {
        $this->attributes = $attributes;

        if ($sync) {
            $this->syncOriginal();
        }

        return $this;
    }

    /**
     * Get the model's original attribute values.
	 * 得到模型的原始属性值
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @return mixed|array
     */
    public function getOriginal($key = null, $default = null)
    {
        return Arr::get($this->original, $key, $default);
    }

    /**
     * Get a subset of the model's attributes.
	 * 得到模型属性的子集
     *
     * @param  array|mixed  $attributes
     * @return array
     */
    public function only($attributes)
    {
        $results = [];

        foreach (is_array($attributes) ? $attributes : func_get_args() as $attribute) {
            $results[$attribute] = $this->getAttribute($attribute);
        }

        return $results;
    }

    /**
     * Sync the original attributes with the current.
	 * 将原始属性与当前属性同步
     *
     * @return $this
     */
    public function syncOriginal()
    {
        $this->original = $this->attributes;

        return $this;
    }

    /**
     * Sync a single original attribute with its current value.
	 * 将单个原始属性与其当前值同步
     *
     * @param  string  $attribute
     * @return $this
     */
    public function syncOriginalAttribute($attribute)
    {
        return $this->syncOriginalAttributes($attribute);
    }

    /**
     * Sync multiple original attribute with their current values.
	 * 将多个原始属性与其当前值同步
     *
     * @param  array|string  $attributes
     * @return $this
     */
    public function syncOriginalAttributes($attributes)
    {
        $attributes = is_array($attributes) ? $attributes : func_get_args();

        foreach ($attributes as $attribute) {
            $this->original[$attribute] = $this->attributes[$attribute];
        }

        return $this;
    }

    /**
     * Sync the changed attributes.
	 * 同步更改的属性
     *
     * @return $this
     */
    public function syncChanges()
    {
        $this->changes = $this->getDirty();

        return $this;
    }

    /**
     * Determine if the model or any of the given attribute(s) have been modified.
	 * 确定模型或任何给定属性是否已被修改
     *
     * @param  array|string|null  $attributes
     * @return bool
     */
    public function isDirty($attributes = null)
    {
        return $this->hasChanges(
            $this->getDirty(), is_array($attributes) ? $attributes : func_get_args()
        );
    }

    /**
     * Determine if the model and all the given attribute(s) have remained the same.
	 * 确定模型和所有给定属性是否保持不变
     *
     * @param  array|string|null  $attributes
     * @return bool
     */
    public function isClean($attributes = null)
    {
        return ! $this->isDirty(...func_get_args());
    }

    /**
     * Determine if the model or any of the given attribute(s) have been modified.
	 * 确定模型或任何给定属性是否已被修改
     *
     * @param  array|string|null  $attributes
     * @return bool
     */
    public function wasChanged($attributes = null)
    {
        return $this->hasChanges(
            $this->getChanges(), is_array($attributes) ? $attributes : func_get_args()
        );
    }

    /**
     * Determine if any of the given attributes were changed.
	 * 确定是否更改了任何给定属性
     *
     * @param  array  $changes
     * @param  array|string|null  $attributes
     * @return bool
     */
    protected function hasChanges($changes, $attributes = null)
    {
        // If no specific attributes were provided, we will just see if the dirty array
        // already contains any attributes. If it does we will just return that this
        // count is greater than zero. Else, we need to check specific attributes.
		// 如果没有提供特定属性，我们来看看脏数组是否已包含任何属性。
		// 如果是，我们就返回这个数大于0。否则，我们需要检查特定的属性。
        if (empty($attributes)) {
            return count($changes) > 0;
        }

        // Here we will spin through every attribute and see if this is in the array of
        // dirty attributes. If it is, we will return true and if we make it through
        // all of the attributes for the entire array we will return false at end.
		// 这里我们将遍历每个属性，看看它是否在脏属性数组中。
		// 如果是，我们将返回true，如果我们通过了对于整个数组的所有属性，我们将在最后返回false。
        foreach (Arr::wrap($attributes) as $attribute) {
            if (array_key_exists($attribute, $changes)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the attributes that have been changed since last sync.
	 * 得到自上次同步以来已更改的属性
     *
     * @return array
     */
    public function getDirty()
    {
        $dirty = [];

        foreach ($this->getAttributes() as $key => $value) {
            if (! $this->originalIsEquivalent($key, $value)) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    /**
     * Get the attributes that were changed.
	 * 得到已更改的属性
     *
     * @return array
     */
    public function getChanges()
    {
        return $this->changes;
    }

    /**
     * Determine if the new and old values for a given key are equivalent.
	 * 确定给定键的新旧值是否相等
     *
     * @param  string  $key
     * @param  mixed  $current
     * @return bool
     */
    public function originalIsEquivalent($key, $current)
    {
        if (! array_key_exists($key, $this->original)) {
            return false;
        }

        $original = $this->getOriginal($key);

        if ($current === $original) {
            return true;
        } elseif (is_null($current)) {
            return false;
        } elseif ($this->isDateAttribute($key)) {
            return $this->fromDateTime($current) ===
                   $this->fromDateTime($original);
        } elseif ($this->hasCast($key, ['object', 'collection'])) {
            return $this->castAttribute($key, $current) ==
                $this->castAttribute($key, $original);
        } elseif ($this->hasCast($key, ['real', 'float', 'double'])) {
            if (($current === null && $original !== null) || ($current !== null && $original === null)) {
                return false;
            }

            return abs($this->castAttribute($key, $current) - $this->castAttribute($key, $original)) < PHP_FLOAT_EPSILON * 4;
        } elseif ($this->hasCast($key)) {
            return $this->castAttribute($key, $current) ===
                   $this->castAttribute($key, $original);
        }

        return is_numeric($current) && is_numeric($original)
                && strcmp((string) $current, (string) $original) === 0;
    }

    /**
     * Append attributes to query when building a query.
	 * 追加属性至查询在构建查询时
     *
     * @param  array|string  $attributes
     * @return $this
     */
    public function append($attributes)
    {
        $this->appends = array_unique(
            array_merge($this->appends, is_string($attributes) ? func_get_args() : $attributes)
        );

        return $this;
    }

    /**
     * Set the accessors to append to model arrays.
	 * 设置访问器为追加到模型数组
     *
     * @param  array  $appends
     * @return $this
     */
    public function setAppends(array $appends)
    {
        $this->appends = $appends;

        return $this;
    }

    /**
     * Get the mutated attributes for a given instance.
	 * 得到给定实例的突变属性
     *
     * @return array
     */
    public function getMutatedAttributes()
    {
        $class = static::class;

        if (! isset(static::$mutatorCache[$class])) {
            static::cacheMutatedAttributes($class);
        }

        return static::$mutatorCache[$class];
    }

    /**
     * Extract and cache all the mutated attributes of a class.
	 * 提取并缓存类的所有变异属性
     *
     * @param  string  $class
     * @return void
     */
    public static function cacheMutatedAttributes($class)
    {
        static::$mutatorCache[$class] = collect(static::getMutatorMethods($class))->map(function ($match) {
            return lcfirst(static::$snakeAttributes ? Str::snake($match) : $match);
        })->all();
    }

    /**
     * Get all of the attribute mutator methods.
	 * 得到所有属性变异器方法
     *
     * @param  mixed  $class
     * @return array
     */
    protected static function getMutatorMethods($class)
    {
        preg_match_all('/(?<=^|;)get([^;]+?)Attribute(;|$)/', implode(';', get_class_methods($class)), $matches);

        return $matches[1];
    }
}
