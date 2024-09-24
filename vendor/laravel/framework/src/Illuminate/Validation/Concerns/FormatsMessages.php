<?php
/**
 * 验证，格式化消息
 */

namespace Illuminate\Validation\Concerns;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\UploadedFile;

trait FormatsMessages
{
    use ReplacesAttributes;

    /**
     * Get the validation message for an attribute and rule.
	 * 得到属性和规则的验证消息
     *
     * @param  string  $attribute
     * @param  string  $rule
     * @return string
     */
    protected function getMessage($attribute, $rule)
    {
        $inlineMessage = $this->getInlineMessage($attribute, $rule);

        // First we will retrieve the custom message for the validation rule if one
        // exists. If a custom validation message is being used we'll return the
        // custom message, otherwise we'll keep searching for a valid message.
		// 首先，我们将检索验证规则的自定义消息（如果存在）。
		// 如果正在使用自定义验证消息，我们将返回自定义消息，否则我们将继续搜索有效消息。
        if (! is_null($inlineMessage)) {
            return $inlineMessage;
        }

        $lowerRule = Str::snake($rule);

        $customMessage = $this->getCustomMessageFromTranslator(
            $customKey = "validation.custom.{$attribute}.{$lowerRule}"
        );

        // First we check for a custom defined validation message for the attribute
        // and rule. This allows the developer to specify specific messages for
        // only some attributes and rules that need to get specially formed.
		// 首先，我们检查属性和规则的自定义验证消息。
		// 这允许开发人员仅为需要特殊格式的某些属性和规则指定特定消息。
        if ($customMessage !== $customKey) {
            return $customMessage;
        }

        // If the rule being validated is a "size" rule, we will need to gather the
        // specific error message for the type of attribute being validated such
        // as a number, file or string which all have different message types.
		// 如果正在验证的规则是"大小"规则，我们需要收集正在验证的属性类型的特定错误消息，
		// 例如数字、文件或字符串，它们都有不同的消息类型。
        elseif (in_array($rule, $this->sizeRules)) {
            return $this->getSizeMessage($attribute, $rule);
        }

        // Finally, if no developer specified messages have been set, and no other
        // special messages apply for this rule, we will just pull the default
        // messages out of the translator service for this validation rule.
		// 最后，如果没有设置开发人员指定的消息，也没有其他特殊消息适用于此规则，
		// 我们将从翻译器服务中提取此验证规则的默认消息。
        $key = "validation.{$lowerRule}";

        if ($key != ($value = $this->translator->get($key))) {
            return $value;
        }

        return $this->getFromLocalArray(
            $attribute, $lowerRule, $this->fallbackMessages
        ) ?: $key;
    }

    /**
     * Get the proper inline error message for standard and size rules.
	 * 得到标准和大小规则的适当内联错误消息
     *
     * @param  string  $attribute
     * @param  string  $rule
     * @return string|null
     */
    protected function getInlineMessage($attribute, $rule)
    {
        $inlineEntry = $this->getFromLocalArray($attribute, Str::snake($rule));

        return is_array($inlineEntry) && in_array($rule, $this->sizeRules)
                    ? $inlineEntry[$this->getAttributeType($attribute)]
                    : $inlineEntry;
    }

    /**
     * Get the inline message for a rule if it exists.
	 * 得到规则的内联消息(如果存在)
     *
     * @param  string  $attribute
     * @param  string  $lowerRule
     * @param  array|null  $source
     * @return string|null
     */
    protected function getFromLocalArray($attribute, $lowerRule, $source = null)
    {
        $source = $source ?: $this->customMessages;

        $keys = ["{$attribute}.{$lowerRule}", $lowerRule];

        // First we will check for a custom message for an attribute specific rule
        // message for the fields, then we will check for a general custom line
        // that is not attribute specific. If we find either we'll return it.
		// 首先，我们将检查字段的特定属性规则消息的自定义消息，然后我们将检查非特定属性的通用自定义行。
		// 如果我们找到任何一个，我们都会归还。
        foreach ($keys as $key) {
            foreach (array_keys($source) as $sourceKey) {
                if (strpos($sourceKey, '*') !== false) {
                    $pattern = str_replace('\*', '([^.]*)', preg_quote($sourceKey, '#'));

                    if (preg_match('#^'.$pattern.'\z#u', $key) === 1) {
                        return $source[$sourceKey];
                    }

                    continue;
                }

                if (Str::is($sourceKey, $key)) {
                    return $source[$sourceKey];
                }
            }
        }
    }

    /**
     * Get the custom error message from translator.
	 * 得到自定义错误消息从翻译器
     *
     * @param  string  $key
     * @return string
     */
    protected function getCustomMessageFromTranslator($key)
    {
        if (($message = $this->translator->get($key)) !== $key) {
            return $message;
        }

        // If an exact match was not found for the key, we will collapse all of these
        // messages and loop through them and try to find a wildcard match for the
        // given key. Otherwise, we will simply return the key's value back out.
		// 如果找不到密钥的精确匹配，我们将折叠所有这些消息并循环遍历它们，
		// 并尝试为给定的密钥找到通配符匹配。否则，我们将简单地返回键的值。
        $shortKey = preg_replace(
            '/^validation\.custom\./', '', $key
        );

        return $this->getWildcardCustomMessages(Arr::dot(
            (array) $this->translator->get('validation.custom')
        ), $shortKey, $key);
    }

    /**
     * Check the given messages for a wildcard key.
	 * 检查给定的消息是否有通配符键
     *
     * @param  array  $messages
     * @param  string  $search
     * @param  string  $default
     * @return string
     */
    protected function getWildcardCustomMessages($messages, $search, $default)
    {
        foreach ($messages as $key => $message) {
            if ($search === $key || (Str::contains($key, ['*']) && Str::is($key, $search))) {
                return $message;
            }
        }

        return $default;
    }

    /**
     * Get the proper error message for an attribute and size rule.
	 * 得到属性和大小规则的正确错误消息
     *
     * @param  string  $attribute
     * @param  string  $rule
     * @return string
     */
    protected function getSizeMessage($attribute, $rule)
    {
        $lowerRule = Str::snake($rule);

        // There are three different types of size validations. The attribute may be
        // either a number, file, or string so we will check a few things to know
        // which type of value it is and return the correct line for that type.
		// 有三种不同类型的大小验证。该属性可以是数字、文件或字符串，
		// 因此我们将检查一些事情以了解它是哪种类型的值，并返回该类型的正确行。
        $type = $this->getAttributeType($attribute);

        $key = "validation.{$lowerRule}.{$type}";

        return $this->translator->get($key);
    }

    /**
     * Get the data type of the given attribute.
	 * 得到给定属性的数据类型
     *
     * @param  string  $attribute
     * @return string
     */
    protected function getAttributeType($attribute)
    {
        // We assume that the attributes present in the file array are files so that
        // means that if the attribute does not have a numeric rule and the files
        // list doesn't have it we'll just consider it a string by elimination.
		// 我们假设文件数组中存在的属性是文件，这意味着如果属性没有数字规则，
		// 而文件列表也没有，我们只会通过消除将其视为字符串。
        if ($this->hasRule($attribute, $this->numericRules)) {
            return 'numeric';
        } elseif ($this->hasRule($attribute, ['Array'])) {
            return 'array';
        } elseif ($this->getValue($attribute) instanceof UploadedFile) {
            return 'file';
        }

        return 'string';
    }

    /**
     * Replace all error message place-holders with actual values.
	 * 替换所有错误消息占位符用实际值
     *
     * @param  string  $message
     * @param  string  $attribute
     * @param  string  $rule
     * @param  array  $parameters
     * @return string
     */
    public function makeReplacements($message, $attribute, $rule, $parameters)
    {
        $message = $this->replaceAttributePlaceholder(
            $message, $this->getDisplayableAttribute($attribute)
        );

        $message = $this->replaceInputPlaceholder($message, $attribute);

        if (isset($this->replacers[Str::snake($rule)])) {
            return $this->callReplacer($message, $attribute, Str::snake($rule), $parameters, $this);
        } elseif (method_exists($this, $replacer = "replace{$rule}")) {
            return $this->$replacer($message, $attribute, $rule, $parameters);
        }

        return $message;
    }

    /**
     * Get the displayable name of the attribute.
	 * 得到属性的可显示名称
     *
     * @param  string  $attribute
     * @return string
     */
    public function getDisplayableAttribute($attribute)
    {
        $primaryAttribute = $this->getPrimaryAttribute($attribute);

        $expectedAttributes = $attribute != $primaryAttribute
                    ? [$attribute, $primaryAttribute] : [$attribute];

        foreach ($expectedAttributes as $name) {
            // The developer may dynamically specify the array of custom attributes on this
            // validator instance. If the attribute exists in this array it is used over
            // the other ways of pulling the attribute name for this given attributes.
			// 开发人员可以动态指定此验证器实例上的自定义属性数组。
			// 如果该属性存在于此数组中，则将其用于获取此给定属性的属性名称的其他方式。
            if (isset($this->customAttributes[$name])) {
                return $this->customAttributes[$name];
            }

            // We allow for a developer to specify language lines for any attribute in this
            // application, which allows flexibility for displaying a unique displayable
            // version of the attribute name instead of the name used in an HTTP POST.
			// 我们允许开发人员为此应用程序中的任何属性指定语言行，
			// 这允许灵活地显示属性名称的唯一可显示版本，而不是HTTP POST中使用的名称。
            if ($line = $this->getAttributeFromTranslations($name)) {
                return $line;
            }
        }

        // When no language line has been specified for the attribute and it is also
        // an implicit attribute we will display the raw attribute's name and not
        // modify it with any of these replacements before we display the name.
		// 当没有为属性指定语言行并且它也是隐式属性时，我们将显示原始属性的名称，
		// 并且在显示名称之前不会用任何替换来修改它。
        if (isset($this->implicitAttributes[$primaryAttribute])) {
            return ($formatter = $this->implicitAttributesFormatter)
                            ? $formatter($attribute)
                            : $attribute;
        }

        return str_replace('_', ' ', Str::snake($attribute));
    }

    /**
     * Get the given attribute from the attribute translations.
	 * 得到给定的属性从属性转换中
     *
     * @param  string  $name
     * @return string
     */
    protected function getAttributeFromTranslations($name)
    {
        return Arr::get($this->translator->get('validation.attributes'), $name);
    }

    /**
     * Replace the :attribute placeholder in the given message.
	 * 替换给定消息中的:属性占位符
     *
     * @param  string  $message
     * @param  string  $value
     * @return string
     */
    protected function replaceAttributePlaceholder($message, $value)
    {
        return str_replace(
            [':attribute', ':ATTRIBUTE', ':Attribute'],
            [$value, Str::upper($value), Str::ucfirst($value)],
            $message
        );
    }

    /**
     * Replace the :input placeholder in the given message.
	 * 替换给定消息中的:input占位符
     *
     * @param  string  $message
     * @param  string  $attribute
     * @return string
     */
    protected function replaceInputPlaceholder($message, $attribute)
    {
        $actualValue = $this->getValue($attribute);

        if (is_scalar($actualValue) || is_null($actualValue)) {
            $message = str_replace(':input', $actualValue, $message);
        }

        return $message;
    }

    /**
     * Get the displayable name of the value.
	 * 得到值的可显示名称
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return string
     */
    public function getDisplayableValue($attribute, $value)
    {
        if (isset($this->customValues[$attribute][$value])) {
            return $this->customValues[$attribute][$value];
        }

        $key = "validation.values.{$attribute}.{$value}";

        if (($line = $this->translator->get($key)) !== $key) {
            return $line;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_null($value)) {
            return 'empty';
        }

        return $value;
    }

    /**
     * Transform an array of attributes to their displayable form.
	 * 转换属性数组为可显示的形式
     *
     * @param  array  $values
     * @return array
     */
    protected function getAttributeList(array $values)
    {
        $attributes = [];

        // For each attribute in the list we will simply get its displayable form as
        // this is convenient when replacing lists of parameters like some of the
        // replacement functions do when formatting out the validation message.
		// 对于列表中的每个属性，我们将简单地获得其可显示的形式，
		// 因为这在替换参数列表时很方便，就像一些替换函数在格式化验证消息时所做的那样。
        foreach ($values as $key => $value) {
            $attributes[$key] = $this->getDisplayableAttribute($value);
        }

        return $attributes;
    }

    /**
     * Call a custom validator message replacer.
	 * 调用自定义验证器消息替换程序
     *
     * @param  string  $message
     * @param  string  $attribute
     * @param  string  $rule
     * @param  array  $parameters
     * @param  \Illuminate\Validation\Validator  $validator
     * @return string|null
     */
    protected function callReplacer($message, $attribute, $rule, $parameters, $validator)
    {
        $callback = $this->replacers[$rule];

        if ($callback instanceof Closure) {
            return $callback(...func_get_args());
        } elseif (is_string($callback)) {
            return $this->callClassBasedReplacer($callback, $message, $attribute, $rule, $parameters, $validator);
        }
    }

    /**
     * Call a class based validator message replacer.
	 * 调用基于类的验证器消息替换器
     *
     * @param  string  $callback
     * @param  string  $message
     * @param  string  $attribute
     * @param  string  $rule
     * @param  array  $parameters
     * @param  \Illuminate\Validation\Validator  $validator
     * @return string
     */
    protected function callClassBasedReplacer($callback, $message, $attribute, $rule, $parameters, $validator)
    {
        [$class, $method] = Str::parseCallback($callback, 'replace');

        return $this->container->make($class)->{$method}(...array_slice(func_get_args(), 1));
    }
}
