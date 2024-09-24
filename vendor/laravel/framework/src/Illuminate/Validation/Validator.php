<?php
/**
 * 验证器，核心类
 */

namespace Illuminate\Validation;

use BadMethodCallException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Validation\ImplicitRule;
use Illuminate\Contracts\Validation\Rule as RuleContract;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Arr;
use Illuminate\Support\Fluent;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class Validator implements ValidatorContract
{
    use Concerns\FormatsMessages,
        Concerns\ValidatesAttributes;

    /**
     * The Translator implementation.
	 * 翻译器实现
     *
     * @var \Illuminate\Contracts\Translation\Translator
     */
    protected $translator;

    /**
     * The container instance.
	 * 容器实例
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * The Presence Verifier implementation.
	 * 状态验证器实现
     *
     * @var \Illuminate\Validation\PresenceVerifierInterface
     */
    protected $presenceVerifier;

    /**
     * The failed validation rules.
	 * 失败的验证规则
     *
     * @var array
     */
    protected $failedRules = [];

    /**
     * Attributes that should be excluded from the validated data.
	 * 应从验证数据中排除的属性
     *
     * @var array
     */
    protected $excludeAttributes = [];

    /**
     * The message bag instance.
	 * 消息包实例
     *
     * @var \Illuminate\Support\MessageBag
     */
    protected $messages;

    /**
     * The data under validation.
	 * 正在验证的数据
     *
     * @var array
     */
    protected $data;

    /**
     * The initial rules provided.
	 * 提供了初始规则
     *
     * @var array
     */
    protected $initialRules;

    /**
     * The rules to be applied to the data.
	 * 要应用于数据的规则
     *
     * @var array
     */
    protected $rules;

    /**
     * The current rule that is validating.
	 * 正在验证的当前规则
     *
     * @var string
     */
    protected $currentRule;

    /**
     * The array of wildcard attributes with their asterisks expanded.
	 * 扩展了带有星号的通配符属性数组
     *
     * @var array
     */
    protected $implicitAttributes = [];

    /**
     * The callback that should be used to format the attribute.
	 * 应该用于格式化属性的回调
     *
     * @var callable|null
     */
    protected $implicitAttributesFormatter;

    /**
     * The cached data for the "distinct" rule.
	 * 为"distinct"规则缓存的数据
     *
     * @var array
     */
    protected $distinctValues = [];

    /**
     * All of the registered "after" callbacks.
	 * 所有注册的"after"回调
     *
     * @var array
     */
    protected $after = [];

    /**
     * The array of custom error messages.
	 * 自定义错误消息的数组
     *
     * @var array
     */
    public $customMessages = [];

    /**
     * The array of fallback error messages.
	 * 回退错误消息数组
     *
     * @var array
     */
    public $fallbackMessages = [];

    /**
     * The array of custom attribute names.
	 * 自定义属性名称的数组
     *
     * @var array
     */
    public $customAttributes = [];

    /**
     * The array of custom displayable values.
	 * 自定义可显示值的数组
     *
     * @var array
     */
    public $customValues = [];

    /**
     * All of the custom validator extensions.
	 * 所有自定义验证器扩展
     *
     * @var array
     */
    public $extensions = [];

    /**
     * All of the custom replacer extensions.
	 * 所有自定义替换器扩展
     *
     * @var array
     */
    public $replacers = [];

    /**
     * The validation rules that may be applied to files.
	 * 可能应用于文件的验证规则
     *
     * @var array
     */
    protected $fileRules = [
        'File', 'Image', 'Mimes', 'Mimetypes', 'Min',
        'Max', 'Size', 'Between', 'Dimensions',
    ];

    /**
     * The validation rules that imply the field is required.
	 * 隐含该字段的验证规则是必需的
     *
     * @var array
     */
    protected $implicitRules = [
        'Required', 'Filled', 'RequiredWith', 'RequiredWithAll', 'RequiredWithout',
        'RequiredWithoutAll', 'RequiredIf', 'RequiredUnless', 'Accepted', 'Present',
    ];

    /**
     * The validation rules which depend on other fields as parameters.
	 * 依赖其他字段作为参数的验证规则
     *
     * @var array
     */
    protected $dependentRules = [
        'RequiredWith', 'RequiredWithAll', 'RequiredWithout', 'RequiredWithoutAll',
        'RequiredIf', 'RequiredUnless', 'Confirmed', 'Same', 'Different', 'Unique',
        'Before', 'After', 'BeforeOrEqual', 'AfterOrEqual', 'Gt', 'Lt', 'Gte', 'Lte',
        'ExcludeIf', 'ExcludeUnless',
    ];

    /**
     * The validation rules that can exclude an attribute.
	 * 可以排除属性的验证规则
     *
     * @var array
     */
    protected $excludeRules = ['ExcludeIf', 'ExcludeUnless'];

    /**
     * The size related validation rules.
	 * 大小相关的验证规则
     *
     * @var array
     */
    protected $sizeRules = ['Size', 'Between', 'Min', 'Max', 'Gt', 'Lt', 'Gte', 'Lte'];

    /**
     * The numeric related validation rules.
	 * 数字相关的验证规则
     *
     * @var array
     */
    protected $numericRules = ['Numeric', 'Integer'];

    /**
     * The current placeholder for dots in rule keys.
	 * 规则键中点的当前占位符
     *
     * @var string
     */
    protected $dotPlaceholder;

    /**
     * Create a new Validator instance.
	 * 创建新的Validator实例
     *
     * @param  \Illuminate\Contracts\Translation\Translator  $translator
     * @param  array  $data
     * @param  array  $rules
     * @param  array  $messages
     * @param  array  $customAttributes
     * @return void
     */
    public function __construct(Translator $translator, array $data, array $rules,
                                array $messages = [], array $customAttributes = [])
    {
        $this->dotPlaceholder = Str::random();

        $this->initialRules = $rules;
        $this->translator = $translator;
        $this->customMessages = $messages;
        $this->data = $this->parseData($data);
        $this->customAttributes = $customAttributes;

        $this->setRules($rules);
    }

    /**
     * Parse the data array, converting dots to ->.
	 * 解析数据数组，将点转换为->。
     *
     * @param  array  $data
     * @return array
     */
    public function parseData(array $data)
    {
        $newData = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = $this->parseData($value);
            }

            $key = str_replace(
                ['.', '*'],
                [$this->dotPlaceholder, '__asterisk__'],
                $key
            );

            $newData[$key] = $value;
        }

        return $newData;
    }

    /**
     * Add an after validation callback.
	 * 添加一个验证后回调
     *
     * @param  callable|string  $callback
     * @return $this
     */
    public function after($callback)
    {
        $this->after[] = function () use ($callback) {
            return $callback($this);
        };

        return $this;
    }

    /**
     * Determine if the data passes the validation rules.
	 * 确定数据是否通过验证规则
     *
     * @return bool
     */
    public function passes()
    {
        $this->messages = new MessageBag;

        [$this->distinctValues, $this->failedRules] = [[], []];

        // We'll spin through each rule, validating the attributes attached to that
        // rule. Any error messages will be added to the containers with each of
        // the other error messages, returning true if we don't have messages.
		// 我们将遍历每个规则，验证附加到该规则的属性。
		// 任何错误消息都将与其他错误消息一起添加到容器中，如果没有消息，则返回true。
        foreach ($this->rules as $attribute => $rules) {
            if ($this->shouldBeExcluded($attribute)) {
                $this->removeAttribute($attribute);

                continue;
            }

            foreach ($rules as $rule) {
                $this->validateAttribute($attribute, $rule);

                if ($this->shouldBeExcluded($attribute)) {
                    $this->removeAttribute($attribute);

                    break;
                }

                if ($this->shouldStopValidating($attribute)) {
                    break;
                }
            }
        }

        // Here we will spin through all of the "after" hooks on this validator and
        // fire them off. This gives the callbacks a chance to perform all kinds
        // of other validation that needs to get wrapped up in this operation.
		// 在这里，我们将遍历此验证器上的所有"after"钩子并将其关闭。
		// 这使回调有机会执行需要在此操作中完成的各种其他验证。
        foreach ($this->after as $after) {
            $after();
        }

        return $this->messages->isEmpty();
    }

    /**
     * Determine if the data fails the validation rules.
	 * 确定数据是否不符合验证规则
     *
     * @return bool
     */
    public function fails()
    {
        return ! $this->passes();
    }

    /**
     * Determine if the attribute should be excluded.
	 * 确定是否应该排除该属性
     *
     * @param  string  $attribute
     * @return bool
     */
    protected function shouldBeExcluded($attribute)
    {
        foreach ($this->excludeAttributes as $excludeAttribute) {
            if ($attribute === $excludeAttribute ||
                Str::startsWith($attribute, $excludeAttribute.'.')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Remove the given attribute.
	 * 移除给定的属性
     *
     * @param  string  $attribute
     * @return void
     */
    protected function removeAttribute($attribute)
    {
        Arr::forget($this->data, $attribute);
        Arr::forget($this->rules, $attribute);
    }

    /**
     * Run the validator's rules against its data.
	 * 运行验证器的规则针对其数据
     *
     * @return array
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validate()
    {
        if ($this->fails()) {
            throw new ValidationException($this);
        }

        return $this->validated();
    }

    /**
     * Get the attributes and values that were validated.
	 * 得到已验证的属性和值
     *
     * @return array
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validated()
    {
        if ($this->invalid()) {
            throw new ValidationException($this);
        }

        $results = [];

        $missingValue = Str::random(10);

        foreach (array_keys($this->getRules()) as $key) {
            $value = data_get($this->getData(), $key, $missingValue);

            if ($value !== $missingValue) {
                Arr::set($results, $key, $value);
            }
        }

        return $results;
    }

    /**
     * Validate a given attribute against a rule.
	 * 根据规则验证给定的属性
     *
     * @param  string  $attribute
     * @param  string  $rule
     * @return void
     */
    protected function validateAttribute($attribute, $rule)
    {
        $this->currentRule = $rule;

        [$rule, $parameters] = ValidationRuleParser::parse($rule);

        if ($rule == '') {
            return;
        }

        // First we will get the correct keys for the given attribute in case the field is nested in
        // an array. Then we determine if the given rule accepts other field names as parameters.
        // If so, we will replace any asterisks found in the parameters with the correct keys.
		// 首先，如果字段嵌套在数组中，我们将获得给定属性的正确键。
		// 然后我们确定给定的规则是否接受其他字段名作为参数。
		// 如果是这样，我们将用正确的键替换参数中的任何星号。
        if (($keys = $this->getExplicitKeys($attribute)) &&
            $this->dependsOnOtherFields($rule)) {
            $parameters = $this->replaceAsterisksInParameters($parameters, $keys);
        }

        $value = $this->getValue($attribute);

        // If the attribute is a file, we will verify that the file upload was actually successful
        // and if it wasn't we will add a failure for the attribute. Files may not successfully
        // upload if they are too large based on PHP's settings so we will bail in this case.
		// 如果该属性是一个文件，我们将验证文件上传是否真正成功，如果不是，我们将为该属性添加一个失败。
		// 根据PHP的设置，如果文件太大，可能无法成功上传，因此在这种情况下我们将退出。
        if ($value instanceof UploadedFile && ! $value->isValid() &&
            $this->hasRule($attribute, array_merge($this->fileRules, $this->implicitRules))
        ) {
            return $this->addFailure($attribute, 'uploaded', []);
        }

        // If we have made it this far we will make sure the attribute is validatable and if it is
        // we will call the validation method with the attribute. If a method returns false the
        // attribute is invalid and we will add a failure message for this failing attribute.
		// 如果我们已经做到了这一点，我们将确保该属性是可验证的，如果是，我们将使用该属性调用验证方法。
		// 如果方法返回false，则该属性无效，我们将为此失败属性添加失败消息。
        $validatable = $this->isValidatable($rule, $attribute, $value);

        if ($rule instanceof RuleContract) {
            return $validatable
                    ? $this->validateUsingCustomRule($attribute, $value, $rule)
                    : null;
        }

        $method = "validate{$rule}";

        if ($validatable && ! $this->$method($attribute, $value, $parameters, $this)) {
            $this->addFailure($attribute, $rule, $parameters);
        }
    }

    /**
     * Determine if the given rule depends on other fields.
	 * 确定给定的规则是否依赖于其他字段
     *
     * @param  string  $rule
     * @return bool
     */
    protected function dependsOnOtherFields($rule)
    {
        return in_array($rule, $this->dependentRules);
    }

    /**
     * Get the explicit keys from an attribute flattened with dot notation.
	 * 得到显式键从使用点表示法平面化的属性中
     *
     * E.g. 'foo.1.bar.spark.baz' -> [1, 'spark'] for 'foo.*.bar.*.baz'
     *
     * @param  string  $attribute
     * @return array
     */
    protected function getExplicitKeys($attribute)
    {
        $pattern = str_replace('\*', '([^\.]+)', preg_quote($this->getPrimaryAttribute($attribute), '/'));

        if (preg_match('/^'.$pattern.'/', $attribute, $keys)) {
            array_shift($keys);

            return $keys;
        }

        return [];
    }

    /**
     * Get the primary attribute name.
	 * 得到主属性名称
     *
     * For example, if "name.0" is given, "name.*" will be returned.
     *
     * @param  string  $attribute
     * @return string
     */
    protected function getPrimaryAttribute($attribute)
    {
        foreach ($this->implicitAttributes as $unparsed => $parsed) {
            if (in_array($attribute, $parsed)) {
                return $unparsed;
            }
        }

        return $attribute;
    }

    /**
     * Replace each field parameter which has asterisks with the given keys.
	 * 替换每个带有星号的字段参数用给定的键
     *
     * @param  array  $parameters
     * @param  array  $keys
     * @return array
     */
    protected function replaceAsterisksInParameters(array $parameters, array $keys)
    {
        return array_map(function ($field) use ($keys) {
            return vsprintf(str_replace('*', '%s', $field), $keys);
        }, $parameters);
    }

    /**
     * Determine if the attribute is validatable.
	 * 确定属性是否可验证
     *
     * @param  object|string  $rule
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    protected function isValidatable($rule, $attribute, $value)
    {
        if (in_array($rule, $this->excludeRules)) {
            return true;
        }

        return $this->presentOrRuleIsImplicit($rule, $attribute, $value) &&
               $this->passesOptionalCheck($attribute) &&
               $this->isNotNullIfMarkedAsNullable($rule, $attribute) &&
               $this->hasNotFailedPreviousRuleIfPresenceRule($rule, $attribute);
    }

    /**
     * Determine if the field is present, or the rule implies required.
	 * 确定字段是否存在，或者规则暗示需要。
     *
     * @param  object|string  $rule
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    protected function presentOrRuleIsImplicit($rule, $attribute, $value)
    {
        if (is_string($value) && trim($value) === '') {
            return $this->isImplicit($rule);
        }

        return $this->validatePresent($attribute, $value) ||
               $this->isImplicit($rule);
    }

    /**
     * Determine if a given rule implies the attribute is required.
	 * 确定给定规则是否暗示需要该属性
     *
     * @param  object|string  $rule
     * @return bool
     */
    protected function isImplicit($rule)
    {
        return $rule instanceof ImplicitRule ||
               in_array($rule, $this->implicitRules);
    }

    /**
     * Determine if the attribute passes any optional check.
	 * 确定属性是否通过了任何可选检查
     *
     * @param  string  $attribute
     * @return bool
     */
    protected function passesOptionalCheck($attribute)
    {
        if (! $this->hasRule($attribute, ['Sometimes'])) {
            return true;
        }

        $data = ValidationData::initializeAndGatherData($attribute, $this->data);

        return array_key_exists($attribute, $data)
            || array_key_exists($attribute, $this->data);
    }

    /**
     * Determine if the attribute fails the nullable check.
	 * 确定属性是否未通过可空检查
     *
     * @param  string  $rule
     * @param  string  $attribute
     * @return bool
     */
    protected function isNotNullIfMarkedAsNullable($rule, $attribute)
    {
        if ($this->isImplicit($rule) || ! $this->hasRule($attribute, ['Nullable'])) {
            return true;
        }

        return ! is_null(Arr::get($this->data, $attribute, 0));
    }

    /**
     * Determine if it's a necessary presence validation.
	 * 确定它是否是必要的状态验证
     *
     * This is to avoid possible database type comparison errors.
     *
     * @param  string  $rule
     * @param  string  $attribute
     * @return bool
     */
    protected function hasNotFailedPreviousRuleIfPresenceRule($rule, $attribute)
    {
        return in_array($rule, ['Unique', 'Exists']) ? ! $this->messages->has($attribute) : true;
    }

    /**
     * Validate an attribute using a custom rule object.
	 * 验证属性使用自定义规则对象
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  \Illuminate\Contracts\Validation\Rule  $rule
     * @return void
     */
    protected function validateUsingCustomRule($attribute, $value, $rule)
    {
        if (! $rule->passes($attribute, $value)) {
            $this->failedRules[$attribute][get_class($rule)] = [];

            $messages = $rule->message() ? (array) $rule->message() : [get_class($rule)];

            foreach ($messages as $message) {
                $this->messages->add($attribute, $this->makeReplacements(
                    $message, $attribute, get_class($rule), []
                ));
            }
        }
    }

    /**
     * Check if we should stop further validations on a given attribute.
	 * 检查是否应该停止对给定属性的进一步验证
     *
     * @param  string  $attribute
     * @return bool
     */
    protected function shouldStopValidating($attribute)
    {
        if ($this->hasRule($attribute, ['Bail'])) {
            return $this->messages->has($attribute);
        }

        if (isset($this->failedRules[$attribute]) &&
            array_key_exists('uploaded', $this->failedRules[$attribute])) {
            return true;
        }

        // In case the attribute has any rule that indicates that the field is required
        // and that rule already failed then we should stop validation at this point
        // as now there is no point in calling other rules with this field empty.
		// 如果该属性有任何规则表明该字段是必需的，并且该规则已经失败，
		// 那么我们应该在此时停止验证，因为现在没有必要在该字段为空的情况下调用其他规则。
        return $this->hasRule($attribute, $this->implicitRules) &&
               isset($this->failedRules[$attribute]) &&
               array_intersect(array_keys($this->failedRules[$attribute]), $this->implicitRules);
    }

    /**
     * Add a failed rule and error message to the collection.
	 * 添加失败的规则和错误消息至集合
     *
     * @param  string  $attribute
     * @param  string  $rule
     * @param  array  $parameters
     * @return void
     */
    public function addFailure($attribute, $rule, $parameters = [])
    {
        if (! $this->messages) {
            $this->passes();
        }

        $attribute = str_replace('__asterisk__', '*', $attribute);

        if (in_array($rule, $this->excludeRules)) {
            return $this->excludeAttribute($attribute);
        }

        $this->messages->add($attribute, $this->makeReplacements(
            $this->getMessage($attribute, $rule), $attribute, $rule, $parameters
        ));

        $this->failedRules[$attribute][$rule] = $parameters;
    }

    /**
     * Add the given attribute to the list of excluded attributes.
     * 将给定属性添加到排除属性列表中
     * @param  string  $attribute
     * @return void
     */
    protected function excludeAttribute(string $attribute)
    {
        $this->excludeAttributes[] = $attribute;

        $this->excludeAttributes = array_unique($this->excludeAttributes);
    }

    /**
     * Returns the data which was valid.
	 * 返回有效的数据
     *
     * @return array
     */
    public function valid()
    {
        if (! $this->messages) {
            $this->passes();
        }

        return array_diff_key(
            $this->data, $this->attributesThatHaveMessages()
        );
    }

    /**
     * Returns the data which was invalid.
	 * 返回无效的数据
     *
     * @return array
     */
    public function invalid()
    {
        if (! $this->messages) {
            $this->passes();
        }

        $invalid = array_intersect_key(
            $this->data, $this->attributesThatHaveMessages()
        );

        $result = [];

        $failed = Arr::only(Arr::dot($invalid), array_keys($this->failed()));

        foreach ($failed as $key => $failure) {
            Arr::set($result, $key, $failure);
        }

        return $result;
    }

    /**
     * Generate an array of all attributes that have messages.
	 * 生成包含有消息的所有属性的数组
     *
     * @return array
     */
    protected function attributesThatHaveMessages()
    {
        return collect($this->messages()->toArray())->map(function ($message, $key) {
            return explode('.', $key)[0];
        })->unique()->flip()->all();
    }

    /**
     * Get the failed validation rules.
	 * 得到失败的验证规则
     *
     * @return array
     */
    public function failed()
    {
        return $this->failedRules;
    }

    /**
     * Get the message container for the validator.
	 * 得到验证器的消息容器
     *
     * @return \Illuminate\Support\MessageBag
     */
    public function messages()
    {
        if (! $this->messages) {
            $this->passes();
        }

        return $this->messages;
    }

    /**
     * An alternative more semantic shortcut to the message container.
	 * 消息容器的另一种更语义化的快捷方式
     *
     * @return \Illuminate\Support\MessageBag
     */
    public function errors()
    {
        return $this->messages();
    }

    /**
     * Get the messages for the instance.
	 * 得到实例的消息
     *
     * @return \Illuminate\Support\MessageBag
     */
    public function getMessageBag()
    {
        return $this->messages();
    }

    /**
     * Determine if the given attribute has a rule in the given set.
	 * 确定给定属性在给定集合中是否有规则
     *
     * @param  string  $attribute
     * @param  string|array  $rules
     * @return bool
     */
    public function hasRule($attribute, $rules)
    {
        return ! is_null($this->getRule($attribute, $rules));
    }

    /**
     * Get a rule and its parameters for a given attribute.
	 * 得到给定属性的规则及其参数
     *
     * @param  string  $attribute
     * @param  string|array  $rules
     * @return array|null
     */
    protected function getRule($attribute, $rules)
    {
        if (! array_key_exists($attribute, $this->rules)) {
            return;
        }

        $rules = (array) $rules;

        foreach ($this->rules[$attribute] as $rule) {
            [$rule, $parameters] = ValidationRuleParser::parse($rule);

            if (in_array($rule, $rules)) {
                return [$rule, $parameters];
            }
        }
    }

    /**
     * Get the data under validation.
	 * 得到正在验证的数据
     *
     * @return array
     */
    public function attributes()
    {
        return $this->getData();
    }

    /**
     * Get the data under validation.
	 * 得到正在验证的数据
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set the data under validation.
	 * 设置正在验证的数据
     *
     * @param  array  $data
     * @return $this
     */
    public function setData(array $data)
    {
        $this->data = $this->parseData($data);

        $this->setRules($this->initialRules);

        return $this;
    }

    /**
     * Get the value of a given attribute.
	 * 得到给定属性的值
     *
     * @param  string  $attribute
     * @return mixed
     */
    protected function getValue($attribute)
    {
        return Arr::get($this->data, $attribute);
    }

    /**
     * Get the validation rules.
	 * 得到验证规则
     *
     * @return array
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * Set the validation rules.
	 * 设置验证规则
     *
     * @param  array  $rules
     * @return $this
     */
    public function setRules(array $rules)
    {
        $rules = collect($rules)->mapWithKeys(function ($value, $key) {
            return [str_replace('\.', $this->dotPlaceholder, $key) => $value];
        })->toArray();

        $this->initialRules = $rules;

        $this->rules = [];

        $this->addRules($rules);

        return $this;
    }

    /**
     * Parse the given rules and merge them into current rules.
	 * 解析给定的规则并将它们合并到当前规则中
     *
     * @param  array  $rules
     * @return void
     */
    public function addRules($rules)
    {
        // The primary purpose of this parser is to expand any "*" rules to the all
        // of the explicit rules needed for the given data. For example the rule
        // names.* would get expanded to names.0, names.1, etc. for this data.
		// 此解析器的主要目的是将任何"*"规则扩展为给定数据所需的所有显式规则。
		// 例如，对于此数据，规则名称.*将扩展为名称.0、名称.1等。
        $response = (new ValidationRuleParser($this->data))
                            ->explode($rules);

        $this->rules = array_merge_recursive(
            $this->rules, $response->rules
        );

        $this->implicitAttributes = array_merge(
            $this->implicitAttributes, $response->implicitAttributes
        );
    }

    /**
     * Add conditions to a given field based on a Closure.
	 * 根据Closure向给定字段添加条件
     *
     * @param  string|array  $attribute
     * @param  string|array  $rules
     * @param  callable  $callback
     * @return $this
     */
    public function sometimes($attribute, $rules, callable $callback)
    {
        $payload = new Fluent($this->getData());

        if ($callback($payload)) {
            foreach ((array) $attribute as $key) {
                $this->addRules([$key => $rules]);
            }
        }

        return $this;
    }

    /**
     * Register an array of custom validator extensions.
	 * 注册一个自定义验证器扩展数组
     *
     * @param  array  $extensions
     * @return void
     */
    public function addExtensions(array $extensions)
    {
        if ($extensions) {
            $keys = array_map([Str::class, 'snake'], array_keys($extensions));

            $extensions = array_combine($keys, array_values($extensions));
        }

        $this->extensions = array_merge($this->extensions, $extensions);
    }

    /**
     * Register an array of custom implicit validator extensions.
	 * 注册一个自定义隐式验证器扩展数组
     *
     * @param  array  $extensions
     * @return void
     */
    public function addImplicitExtensions(array $extensions)
    {
        $this->addExtensions($extensions);

        foreach ($extensions as $rule => $extension) {
            $this->implicitRules[] = Str::studly($rule);
        }
    }

    /**
     * Register an array of custom dependent validator extensions.
	 * 注册一个自定义依赖验证器扩展数组
     *
     * @param  array  $extensions
     * @return void
     */
    public function addDependentExtensions(array $extensions)
    {
        $this->addExtensions($extensions);

        foreach ($extensions as $rule => $extension) {
            $this->dependentRules[] = Str::studly($rule);
        }
    }

    /**
     * Register a custom validator extension.
	 * 注册一个自定义验证器扩展
     *
     * @param  string  $rule
     * @param  \Closure|string  $extension
     * @return void
     */
    public function addExtension($rule, $extension)
    {
        $this->extensions[Str::snake($rule)] = $extension;
    }

    /**
     * Register a custom implicit validator extension.
	 * 注册自定义隐式验证器扩展
     *
     * @param  string  $rule
     * @param  \Closure|string  $extension
     * @return void
     */
    public function addImplicitExtension($rule, $extension)
    {
        $this->addExtension($rule, $extension);

        $this->implicitRules[] = Str::studly($rule);
    }

    /**
     * Register a custom dependent validator extension.
	 * 注册自定义依赖验证器扩展
     *
     * @param  string  $rule
     * @param  \Closure|string  $extension
     * @return void
     */
    public function addDependentExtension($rule, $extension)
    {
        $this->addExtension($rule, $extension);

        $this->dependentRules[] = Str::studly($rule);
    }

    /**
     * Register an array of custom validator message replacers.
	 * 注册一个自定义验证器消息替换器数组
     *
     * @param  array  $replacers
     * @return void
     */
    public function addReplacers(array $replacers)
    {
        if ($replacers) {
            $keys = array_map([Str::class, 'snake'], array_keys($replacers));

            $replacers = array_combine($keys, array_values($replacers));
        }

        $this->replacers = array_merge($this->replacers, $replacers);
    }

    /**
     * Register a custom validator message replacer.
	 * 注册一个自定义验证器消息替换程序
     *
     * @param  string  $rule
     * @param  \Closure|string  $replacer
     * @return void
     */
    public function addReplacer($rule, $replacer)
    {
        $this->replacers[Str::snake($rule)] = $replacer;
    }

    /**
     * Set the custom messages for the validator.
	 * 设置自定义消息为验证器
     *
     * @param  array  $messages
     * @return $this
     */
    public function setCustomMessages(array $messages)
    {
        $this->customMessages = array_merge($this->customMessages, $messages);

        return $this;
    }

    /**
     * Set the custom attributes on the validator.
	 * 设置自定义属性在验证器上
     *
     * @param  array  $attributes
     * @return $this
     */
    public function setAttributeNames(array $attributes)
    {
        $this->customAttributes = $attributes;

        return $this;
    }

    /**
     * Add custom attributes to the validator.
	 * 向验证器添加自定义属性
     *
     * @param  array  $customAttributes
     * @return $this
     */
    public function addCustomAttributes(array $customAttributes)
    {
        $this->customAttributes = array_merge($this->customAttributes, $customAttributes);

        return $this;
    }

    /**
     * Set the callback that used to format an implicit attribute.
	 * 设置用于格式化隐式属性的回调
     *
     * @param  callable|null  $formatter
     * @return $this
     */
    public function setImplicitAttributesFormatter(callable $formatter = null)
    {
        $this->implicitAttributesFormatter = $formatter;

        return $this;
    }

    /**
     * Set the custom values on the validator.
	 * 设置自定义值在验证器上
     *
     * @param  array  $values
     * @return $this
     */
    public function setValueNames(array $values)
    {
        $this->customValues = $values;

        return $this;
    }

    /**
     * Add the custom values for the validator.
	 * 添加自定义值为验证器
     *
     * @param  array  $customValues
     * @return $this
     */
    public function addCustomValues(array $customValues)
    {
        $this->customValues = array_merge($this->customValues, $customValues);

        return $this;
    }

    /**
     * Set the fallback messages for the validator.
	 * 为验证器设置回退消息
     *
     * @param  array  $messages
     * @return void
     */
    public function setFallbackMessages(array $messages)
    {
        $this->fallbackMessages = $messages;
    }

    /**
     * Get the Presence Verifier implementation.
	 * 得到Presence Verifier实现
     *
     * @return \Illuminate\Validation\PresenceVerifierInterface
     *
     * @throws \RuntimeException
     */
    public function getPresenceVerifier()
    {
        if (! isset($this->presenceVerifier)) {
            throw new RuntimeException('Presence verifier has not been set.');
        }

        return $this->presenceVerifier;
    }

    /**
     * Get the Presence Verifier implementation.
	 * 得到Presence Verifier实现
     *
     * @param  string  $connection
     * @return \Illuminate\Validation\PresenceVerifierInterface
     *
     * @throws \RuntimeException
     */
    public function getPresenceVerifierFor($connection)
    {
        return tap($this->getPresenceVerifier(), function ($verifier) use ($connection) {
            $verifier->setConnection($connection);
        });
    }

    /**
     * Set the Presence Verifier implementation.
	 * 设置状态验证器实现
     *
     * @param  \Illuminate\Validation\PresenceVerifierInterface  $presenceVerifier
     * @return void
     */
    public function setPresenceVerifier(PresenceVerifierInterface $presenceVerifier)
    {
        $this->presenceVerifier = $presenceVerifier;
    }

    /**
     * Get the Translator implementation.
	 * 得到翻译机实现
     *
     * @return \Illuminate\Contracts\Translation\Translator
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * Set the Translator implementation.
	 * 设置翻译机实现
     *
     * @param  \Illuminate\Contracts\Translation\Translator  $translator
     * @return void
     */
    public function setTranslator(Translator $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Set the IoC container instance.
	 * 设置IoC容器实例
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return void
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Call a custom validator extension.
	 * 调用自定义验证器扩展
     *
     * @param  string  $rule
     * @param  array  $parameters
     * @return bool|null
     */
    protected function callExtension($rule, $parameters)
    {
        $callback = $this->extensions[$rule];

        if (is_callable($callback)) {
            return $callback(...array_values($parameters));
        } elseif (is_string($callback)) {
            return $this->callClassBasedExtension($callback, $parameters);
        }
    }

    /**
     * Call a class based validator extension.
	 * 调用基于类的验证器扩展
     *
     * @param  string  $callback
     * @param  array  $parameters
     * @return bool
     */
    protected function callClassBasedExtension($callback, $parameters)
    {
        [$class, $method] = Str::parseCallback($callback, 'validate');

        return $this->container->make($class)->{$method}(...array_values($parameters));
    }

    /**
     * Handle dynamic calls to class methods.
	 * 处理对类方法的动态调用
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        $rule = Str::snake(substr($method, 8));

        if (isset($this->extensions[$rule])) {
            return $this->callExtension($rule, $parameters);
        }

        throw new BadMethodCallException(sprintf(
            'Method %s::%s does not exist.', static::class, $method
        ));
    }
}
