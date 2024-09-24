<?php
/**
 * 验证，验证属性
 */

namespace Illuminate\Validation\Concerns;

use Countable;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\NoRFCWarningsValidation;
use Egulias\EmailValidator\Validation\RFCValidation;
use Egulias\EmailValidator\Validation\SpoofCheckValidation;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\ValidationData;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Throwable;

trait ValidatesAttributes
{
    /**
     * Validate that an attribute was "accepted".
	 * 验证一个属性是否被"接受"
     *
     * This validation rule implies the attribute is "required".
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function validateAccepted($attribute, $value)
    {
        $acceptable = ['yes', 'on', '1', 1, true, 'true'];

        return $this->validateRequired($attribute, $value) && in_array($value, $acceptable, true);
    }

    /**
     * Validate that an attribute is an active URL.
	 * 验证属性是否为活动URL
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function validateActiveUrl($attribute, $value)
    {
        if (! is_string($value)) {
            return false;
        }

        if ($url = parse_url($value, PHP_URL_HOST)) {
            try {
                return count(dns_get_record($url.'.', DNS_A | DNS_AAAA)) > 0;
            } catch (Exception $e) {
                return false;
            }
        }

        return false;
    }

    /**
     * "Break" on first validation fail.
	 * 第一次验证失败时"中断"
     *
     * Always returns true, just lets us put "bail" in rules.
     *
     * @return bool
     */
    public function validateBail()
    {
        return true;
    }

    /**
     * Validate the date is before a given date.
	 * 验证日期在给定日期之前
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array  $parameters
     * @return bool
     */
    public function validateBefore($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'before');

        return $this->compareDates($attribute, $value, $parameters, '<');
    }

    /**
     * Validate the date is before or equal a given date.
	 * 验证日期在给定日期之前或等于给定日期
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array  $parameters
     * @return bool
     */
    public function validateBeforeOrEqual($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'before_or_equal');

        return $this->compareDates($attribute, $value, $parameters, '<=');
    }

    /**
     * Validate the date is after a given date.
	 * 验证日期在给定日期之后
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array  $parameters
     * @return bool
     */
    public function validateAfter($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'after');

        return $this->compareDates($attribute, $value, $parameters, '>');
    }

    /**
     * Validate the date is equal or after a given date.
	 * 验证日期是否等于或在给定日期之后
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array  $parameters
     * @return bool
     */
    public function validateAfterOrEqual($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'after_or_equal');

        return $this->compareDates($attribute, $value, $parameters, '>=');
    }

    /**
     * Compare a given date against another using an operator.
	 * 使用操作符将给定日期与另一个日期进行比较
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array  $parameters
     * @param  string  $operator
     * @return bool
     */
    protected function compareDates($attribute, $value, $parameters, $operator)
    {
        if (! is_string($value) && ! is_numeric($value) && ! $value instanceof DateTimeInterface) {
            return false;
        }

        if ($format = $this->getDateFormat($attribute)) {
            return $this->checkDateTimeOrder($format, $value, $parameters[0], $operator);
        }

        if (! $date = $this->getDateTimestamp($parameters[0])) {
            $date = $this->getDateTimestamp($this->getValue($parameters[0]));
        }

        return $this->compare($this->getDateTimestamp($value), $date, $operator);
    }

    /**
     * Get the date format for an attribute if it has one.
	 * 得到属性的日期格式(如果属性有)
     *
     * @param  string  $attribute
     * @return string|null
     */
    protected function getDateFormat($attribute)
    {
        if ($result = $this->getRule($attribute, 'DateFormat')) {
            return $result[1][0];
        }
    }

    /**
     * Get the date timestamp.
	 * 得到日期时间戳
     *
     * @param  mixed  $value
     * @return int
     */
    protected function getDateTimestamp($value)
    {
        if ($value instanceof DateTimeInterface) {
            return $value->getTimestamp();
        }

        if ($this->isTestingRelativeDateTime($value)) {
            $date = $this->getDateTime($value);

            if (! is_null($date)) {
                return $date->getTimestamp();
            }
        }

        return strtotime($value);
    }

    /**
     * Given two date/time strings, check that one is after the other.
	 * 给定两个日期/时间字符串，检查其中一个是否在另一个之后。
     *
     * @param  string  $format
     * @param  string  $first
     * @param  string  $second
     * @param  string  $operator
     * @return bool
     */
    protected function checkDateTimeOrder($format, $first, $second, $operator)
    {
        $firstDate = $this->getDateTimeWithOptionalFormat($format, $first);

        if (! $secondDate = $this->getDateTimeWithOptionalFormat($format, $second)) {
            $secondDate = $this->getDateTimeWithOptionalFormat($format, $this->getValue($second));
        }

        return ($firstDate && $secondDate) && ($this->compare($firstDate, $secondDate, $operator));
    }

    /**
     * Get a DateTime instance from a string.
	 * 得到DateTime实例从字符串中
     *
     * @param  string  $format
     * @param  string  $value
     * @return \DateTime|null
     */
    protected function getDateTimeWithOptionalFormat($format, $value)
    {
        if ($date = DateTime::createFromFormat('!'.$format, $value)) {
            return $date;
        }

        return $this->getDateTime($value);
    }

    /**
     * Get a DateTime instance from a string with no format.
	 * 得到DateTime实例从没有格式的字符串中
     *
     * @param  string  $value
     * @return \DateTime|null
     */
    protected function getDateTime($value)
    {
        try {
            if ($this->isTestingRelativeDateTime($value)) {
                return @Date::parse($value) ?: null;
            }

            return date_create($value) ?: null;
        } catch (Exception $e) {
            //
        }
    }

    /**
     * Check if the given value should be adjusted to Carbon::getTestNow().
	 * 检查给定的值是否应该调整为Carbon::getTestNow()
     *
     * @param  mixed  $value
     * @return bool
     */
    protected function isTestingRelativeDateTime($value)
    {
        return Carbon::hasTestNow() && is_string($value) && (
            $value === 'now' || Carbon::hasRelativeKeywords($value)
        );
    }

    /**
     * Validate that an attribute contains only alphabetic characters.
	 * 验证属性是否只包含字母字符
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function validateAlpha($attribute, $value)
    {
        return is_string($value) && preg_match('/^[\pL\pM]+$/u', $value);
    }

    /**
     * Validate that an attribute contains only alpha-numeric characters, dashes, and underscores.
	 * 验证属性是否只包含字母数字字符、破折号和下划线。
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function validateAlphaDash($attribute, $value)
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }

        return preg_match('/^[\pL\pM\pN_-]+$/u', $value) > 0;
    }

    /**
     * Validate that an attribute contains only alpha-numeric characters.
	 * 验证属性是否只包含字母数字字符
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function validateAlphaNum($attribute, $value)
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }

        return preg_match('/^[\pL\pM\pN]+$/u', $value) > 0;
    }

    /**
     * Validate that an attribute is an array.
	 * 验证属性是否为数组
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function validateArray($attribute, $value)
    {
        return is_array($value);
    }

    /**
     * Validate the size of an attribute is between a set of values.
	 * 验证属性的大小是否在一组值之间
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array  $parameters
     * @return bool
     */
    public function validateBetween($attribute, $value, $parameters)
    {
        $this->requireParameterCount(2, $parameters, 'between');

        $size = $this->getSize($attribute, $value);

        return $size >= $parameters[0] && $size <= $parameters[1];
    }

    /**
     * Validate that an attribute is a boolean.
	 * 验证属性是否为布尔值
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function validateBoolean($attribute, $value)
    {
        $acceptable = [true, false, 0, 1, '0', '1'];

        return in_array($value, $acceptable, true);
    }

    /**
     * Validate that an attribute has a matching confirmation.
	 * 验证属性是否具有匹配确认
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function validateConfirmed($attribute, $value)
    {
        return $this->validateSame($attribute, $value, [$attribute.'_confirmation']);
    }

    /**
     * Validate that an attribute is a valid date.
	 * 验证属性是否为有效日期
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function validateDate($attribute, $value)
    {
        if ($value instanceof DateTimeInterface) {
            return true;
        }

        if ((! is_string($value) && ! is_numeric($value)) || strtotime($value) === false) {
            return false;
        }

        $date = date_parse($value);

        return checkdate($date['month'], $date['day'], $date['year']);
    }

    /**
     * Validate that an attribute matches a date format.
	 * 验证属性是否与日期格式匹配
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array  $parameters
     * @return bool
     */
    public function validateDateFormat($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'date_format');

        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }

        $format = $parameters[0];

        $date = DateTime::createFromFormat('!'.$format, $value);

        return $date && $date->format($format) == $value;
    }

    /**
     * Validate that an attribute is equal to another date.
	 * 验证属性是否等于另一个日期
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array  $parameters
     * @return bool
     */
    public function validateDateEquals($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'date_equals');

        return $this->compareDates($attribute, $value, $parameters, '=');
    }

    /**
     * Validate that an attribute is different from another attribute.
	 * 验证一个属性与另一个属性是否不同
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array  $parameters
     * @return bool
     */
    public function validateDifferent($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'different');

        foreach ($parameters as $parameter) {
            if (! Arr::has($this->data, $parameter)) {
                return false;
            }

            $other = Arr::get($this->data, $parameter);

            if ($value === $other) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate that an attribute has a given number of digits.
	 * 验证属性是否具有给定的位数
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array  $parameters
     * @return bool
     */
    public function validateDigits($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'digits');

        return ! preg_match('/[^0-9]/', $value)
                    && strlen((string) $value) == $parameters[0];
    }

    /**
     * Validate that an attribute is between a given number of digits.
	 * 验证属性是否在给定的位数之间
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array  $parameters
     * @return bool
     */
    public function validateDigitsBetween($attribute, $value, $parameters)
    {
        $this->requireParameterCount(2, $parameters, 'digits_between');

        $length = strlen((string) $value);

        return ! preg_match('/[^0-9.]/', $value)
                    && $length >= $parameters[0] && $length <= $parameters[1];
    }

    /**
     * Validate the dimensions of an image matches the given values.
	 * 验证图像的尺寸是否与给定值匹配
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array  $parameters
     * @return bool
     */
    public function validateDimensions($attribute, $value, $parameters)
    {
        if ($this->isValidFileInstance($value) && in_array($value->getMimeType(), ['image/svg+xml', 'image/svg'])) {
            return true;
        }

        if (! $this->isValidFileInstance($value) || ! $sizeDetails = @getimagesize($value->getRealPath())) {
            return false;
        }

        $this->requireParameterCount(1, $parameters, 'dimensions');

        [$width, $height] = $sizeDetails;

        $parameters = $this->parseNamedParameters($parameters);

        if ($this->failsBasicDimensionChecks($parameters, $width, $height) ||
            $this->failsRatioCheck($parameters, $width, $height)) {
            return false;
        }

        return true;
    }

    /**
     * Test if the given width and height fail any conditions.
	 * 测试给定的宽度和高度是否满足任何条件
     *
     * @param  array  $parameters
     * @param  int  $width
     * @param  int  $height
     * @return bool
     */
    protected function failsBasicDimensionChecks($parameters, $width, $height)
    {
        return (isset($parameters['width']) && $parameters['width'] != $width) ||
               (isset($parameters['min_width']) && $parameters['min_width'] > $width) ||
               (isset($parameters['max_width']) && $parameters['max_width'] < $width) ||
               (isset($parameters['height']) && $parameters['height'] != $height) ||
               (isset($parameters['min_height']) && $parameters['min_height'] > $height) ||
               (isset($parameters['max_height']) && $parameters['max_height'] < $height);
    }

    /**
     * Determine if the given parameters fail a dimension ratio check.
	 * 确定给定参数是否未通过尺寸比率检查
     *
     * @param  array  $parameters
     * @param  int  $width
     * @param  int  $height
     * @return bool
     */
    protected function failsRatioCheck($parameters, $width, $height)
    {
        if (! isset($parameters['ratio'])) {
            return false;
        }

        [$numerator, $denominator] = array_replace(
            [1, 1], array_filter(sscanf($parameters['ratio'], '%f/%d'))
        );

        $precision = 1 / (max($width, $height) + 1);

        return abs($numerator / $denominator - $width / $height) > $precision;
    }

    /**
     * Validate an attribute is unique among other values.
	 * 验证属性在其他值中是唯一的
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array  $parameters
     * @return bool
     */
    public function validateDistinct($attribute, $value, $parameters)
    {
        $data = Arr::except($this->getDistinctValues($attribute), $attribute);

        if (in_array('ignore_case', $parameters)) {
            return empty(preg_grep('/^'.preg_quote($value, '/').'$/iu', $data));
        }

        return ! in_array($value, array_values($data));
    }

    /**
     * Get the values to distinct between.
	 * 得到不同的值
     *
     * @param  string  $attribute
     * @return array
     */
    protected function getDistinctValues($attribute)
    {
        $attributeName = $this->getPrimaryAttribute($attribute);

        if (! property_exists($this, 'distinctValues')) {
            return $this->extractDistinctValues($attributeName);
        }

        if (! array_key_exists($attributeName, $this->distinctValues)) {
            $this->distinctValues[$attributeName] = $this->extractDistinctValues($attributeName);
        }

        return $this->distinctValues[$attributeName];
    }

    /**
     * Extract the distinct values from the data.
	 * 提取不同的值从数据中
     *
     * @param  string  $attribute
     * @return array
     */
    protected function extractDistinctValues($attribute)
    {
        $attributeData = ValidationData::extractDataFromPath(
            ValidationData::getLeadingExplicitAttributePath($attribute), $this->data
        );

        $pattern = str_replace('\*', '[^.]+', preg_quote($attribute, '#'));

        return Arr::where(Arr::dot($attributeData), function ($value, $key) use ($pattern) {
            return (bool) preg_match('#^'.$pattern.'\z#u', $key);
        });
    }

    /**
     * Validate that an attribute is a valid e-mail address.
	 * 验证属性是否为有效的电子邮件地址
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array  $parameters
     * @return bool
     */
    public function validateEmail($attribute, $value, $parameters)
    {
        if (! is_string($value) && ! (is_object($value) && method_exists($value, '__toString'))) {
            return false;
        }

        $validations = collect($parameters)
            ->unique()
            ->map(function ($validation) {
                if ($validation === 'rfc') {
                    return new RFCValidation();
                } elseif ($validation === 'strict') {
                    return new NoRFCWarningsValidation();
                } elseif ($validation === 'dns') {
                    return new DNSCheckValidation();
                } elseif ($validation === 'spoof') {
                    return new SpoofCheckValidation();
                } elseif ($validation === 'filter') {
                    return new FilterEmailValidation();
                }
            })
            ->values()
            ->all() ?: [new RFCValidation()];

        return (new EmailValidator)->isValid($value, new MultipleValidationWithAnd($validations));
    }

    /**
     * Validate the existence of an attribute value in a database table.
	 * 验证数据库表中是否存在属性值
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array  $parameters
     * @return bool
     */
    public function validateExists($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'exists');

        [$connection, $table] = $this->parseTable($parameters[0]);

        // The second parameter position holds the name of the column that should be
        // verified as existing. If this parameter is not specified we will guess
        // that the columns being "verified" shares the given attribute's name.
		// 第二个参数位置包含应验证为存在的列的名称。
		// 如果未指定此参数，我们将猜测正在“验证”的列共享给定属性的名称。
        $column = $this->getQueryColumn($parameters, $attribute);

        $expected = is_array($value) ? count(array_unique($value)) : 1;

        return $this->getExistCount(
            $connection, $table, $column, $value, $parameters
        ) >= $expected;
    }

    /**
     * Get the number of records that exist in storage.
	 * 得到存储中存在的记录数
     *
     * @param  mixed  $connection
     * @param  string  $table
     * @param  string  $column
     * @param  mixed  $value
     * @param  array  $parameters
     * @return int
     */
    protected function getExistCount($connection, $table, $column, $value, $parameters)
    {
        $verifier = $this->getPresenceVerifierFor($connection);

        $extra = $this->getExtraConditions(
            array_values(array_slice($parameters, 2))
        );

        if ($this->currentRule instanceof Exists) {
            $extra = array_merge($extra, $this->currentRule->queryCallbacks());
        }

        return is_array($value)
                ? $verifier->getMultiCount($table, $column, $value, $extra)
                : $verifier->getCount($table, $column, $value, null, null, $extra);
    }

    /**
     * Validate the uniqueness of an attribute value on a given database table.
	 * 验证给定数据库表上属性值的唯一性
     *
     * If a database column is not specified, the attribute will be used.
	 * 如果未指定数据库列，则使用该属性。
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array  $parameters
     * @return bool
     */
    public function validateUnique($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'unique');

        [$connection, $table] = $this->parseTable($parameters[0]);

        // The second parameter position holds the name of the column that needs to
        // be verified as unique. If this parameter isn't specified we will just
        // assume that this column to be verified shares the attribute's name.
		// 第二个参数位置保存需要验证为唯一的列的名称。
		// 如果没有指定此参数，我们将假设要验证的列共享该属性的名称。
        $column = $this->getQueryColumn($parameters, $attribute);

        [$idColumn, $id] = [null, null];

        if (isset($parameters[2])) {
            [$idColumn, $id] = $this->getUniqueIds($parameters);

            if (! is_null($id)) {
                $id = stripslashes($id);
            }
        }

        // The presence verifier is responsible for counting rows within this store
        // mechanism which might be a relational database or any other permanent
        // data store like Redis, etc. We will use it to determine uniqueness.
		// 存在验证器负责计算此存储机制中的行数，该存储机制可能是关系数据库或任何其他永久数据存储，
		// 如Redis等。我们将使用它来确定唯一性。
        $verifier = $this->getPresenceVerifierFor($connection);

        $extra = $this->getUniqueExtra($parameters);

        if ($this->currentRule instanceof Unique) {
            $extra = array_merge($extra, $this->currentRule->queryCallbacks());
        }

        return $verifier->getCount(
            $table, $column, $value, $id, $idColumn, $extra
        ) == 0;
    }

    /**
     * Get the excluded ID column and value for the unique rule.
	 * 得到唯一规则的排除ID列和值
     *
     * @param  array  $parameters
     * @return array
     */
    protected function getUniqueIds($parameters)
    {
        $idColumn = $parameters[3] ?? 'id';

        return [$idColumn, $this->prepareUniqueId($parameters[2])];
    }

    /**
     * Prepare the given ID for querying.
	 * 为查询准备给定的ID
     *
     * @param  mixed  $id
     * @return int
     */
    protected function prepareUniqueId($id)
    {
        if (preg_match('/\[(.*)\]/', $id, $matches)) {
            $id = $this->getValue($matches[1]);
        }

        if (strtolower($id) === 'null') {
            $id = null;
        }

        if (filter_var($id, FILTER_VALIDATE_INT) !== false) {
            $id = (int) $id;
        }

        return $id;
    }

    /**
     * Get the extra conditions for a unique rule.
	 * 得到唯一规则的额外条件
     *
     * @param  array  $parameters
     * @return array
     */
    protected function getUniqueExtra($parameters)
    {
        if (isset($parameters[4])) {
            return $this->getExtraConditions(array_slice($parameters, 4));
        }

        return [];
    }

    /**
     * Parse the connection / table for the unique / exists rules.
	 * 解析连接/表中唯一/存在的规则
     *
     * @param  string  $table
     * @return array
     */
    public function parseTable($table)
    {
        [$connection, $table] = Str::contains($table, '.') ? explode('.', $table, 2) : [null, $table];

        if (Str::contains($table, '\\') && class_exists($table) && is_a($table, Model::class, true)) {
            $model = new $table;

            $table = $model->getTable();

            $connection = $connection ?? $model->getConnectionName();
        }

        return [$connection, $table];
    }

    /**
     * Get the column name for an exists / unique query.
	 * 得到存在/唯一查询的列名
     *
     * @param  array  $parameters
     * @param  string  $attribute
     * @return bool
     */
    public function getQueryColumn($parameters, $attribute)
    {
        return isset($parameters[1]) && $parameters[1] !== 'NULL'
                    ? $parameters[1] : $this->guessColumnForQuery($attribute);
    }

    /**
     * Guess the database column from the given attribute name.
	 * 猜测数据库列根据给定的属性名
     *
     * @param  string  $attribute
     * @return string
     */
    public function guessColumnForQuery($attribute)
    {
        if (in_array($attribute, Arr::collapse($this->implicitAttributes))
                && ! is_numeric($last = last(explode('.', $attribute)))) {
            return $last;
        }

        return $attribute;
    }

    /**
     * Get the extra conditions for a unique / exists rule.
	 * 得到唯一/存在规则的额外条件
     *
     * @param  array  $segments
     * @return array
     */
    protected function getExtraConditions(array $segments)
    {
        $extra = [];

        $count = count($segments);

        for ($i = 0; $i < $count; $i += 2) {
            $extra[$segments[$i]] = $segments[$i + 1];
        }

        return $extra;
    }

    /**
     * Validate the given value is a valid file.
	 * 验证给定值是否为有效文件
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function validateFile($attribute, $value)
    {
        return $this->isValidFileInstance($value);
    }

    /**
     * Validate the given attribute is filled if it is present.
	 * 如果给定的属性存在，验证它是否被填充。
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function validateFilled($attribute, $value)
    {
        if (Arr::has($this->data, $attribute)) {
            return $this->validateRequired($attribute, $value);
        }

        return true;
    }

    /**
     * Validate that an attribute is greater than another attribute.
	 * 验证一个属性是否大于另一个属性
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array  $parameters
     * @return bool
     */
    public function validateGt($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'gt');

        $comparedToValue = $this->getValue($parameters[0]);

        $this->shouldBeNumeric($attribute, 'Gt');

        if (is_null($comparedToValue) && (is_numeric($value) && is_numeric($parameters[0]))) {
            return $this->getSize($attribute, $value) > $parameters[0];
        }

        if (is_numeric($parameters[0])) {
            return false;
        }

        if ($this->hasRule($attribute, $this->numericRules) && is_numeric($value) && is_numeric($comparedToValue)) {
            return $value > $comparedToValue;
        }

        if (! $this->isSameType($value, $comparedToValue)) {
            return false;
        }

        return $this->getSize($attribute, $value) > $this->getSize($attribute, $comparedToValue);
    }

    /**
     * Validate that an attribute is less than another attribute.
	 * 确认一个属性小于另一个属性
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array  $parameters
     * @return bool
     */
    public function validateLt($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'lt');

        $comparedToValue = $this->getValue($parameters[0]);

        $this->shouldBeNumeric($attribute, 'Lt');

        if (is_null($comparedToValue) && (is_numeric($value) && is_numeric($parameters[0]))) {
            return $this->getSize($attribute, $value) < $parameters[0];
        }

        if (is_numeric($parameters[0])) {
            return false;
        }

        if ($this->hasRule($attribute, $this->numericRules) && is_numeric($value) && is_numeric($comparedToValue)) {
            return $value < $comparedToValue;
        }

        if (! $this->isSameType($value, $comparedToValue)) {
            return false;
        }

        return $this->getSize($attribute, $value) < $this->getSize($attribute, $comparedToValue);
    }

    /**
     * Validate that an attribute is greater than or equal another attribute.
	 * 验证一个属性是否大于或等于另一个属性
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array  $parameters
     * @return bool
     */
    public function validateGte($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'gte');

        $comparedToValue = $this->getValue($parameters[0]);

        $this->shouldBeNumeric($attribute, 'Gte');

        if (is_null($comparedToValue) && (is_numeric($value) && is_numeric($parameters[0]))) {
            return $this->getSize($attribute, $value) >= $parameters[0];
        }

        if (is_numeric($parameters[0])) {
            return false;
        }

        if ($this->hasRule($attribute, $this->numericRules) && is_numeric($value) && is_numeric($comparedToValue)) {
            return $value >= $comparedToValue;
        }

        if (! $this->isSameType($value, $comparedToValue)) {
            return false;
        }

        return $this->getSize($attribute, $value) >= $this->getSize($attribute, $comparedToValue);
    }

    /**
     * Validate that an attribute is less than or equal another attribute.
	 * 验证一个属性是否小于或等于另一个属性
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array  $parameters
     * @return bool
     */
    public function validateLte($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'lte');

        $comparedToValue = $this->getValue($parameters[0]);

        $this->shouldBeNumeric($attribute, 'Lte');

        if (is_null($comparedToValue) && (is_numeric($value) && is_numeric($parameters[0]))) {
            return $this->getSize($attribute, $value) <= $parameters[0];
        }

        if (is_numeric($parameters[0])) {
            return false;
        }

        if ($this->hasRule($attribute, $this->numericRules) && is_numeric($value) && is_numeric($comparedToValue)) {
            return $value <= $comparedToValue;
        }

        if (! $this->isSameType($value, $comparedToValue)) {
            return false;
        }

        return $this->getSize($attribute, $value) <= $this->getSize($attribute, $comparedToValue);
    }

    /**
     * Validate the MIME type of a file is an image MIME type.
	 * 验证文件的MIME类型是否为图像MIME类型
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function validateImage($attribute, $value)
    {
        return $this->validateMimes($attribute, $value, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp']);
    }

    /**
     * Validate an attribute is contained within a list of values.
	 * 验证属性是否包含在值列表中
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array  $parameters
     * @return bool
     */
    public function validateIn($attribute, $value, $parameters)
    {
        if (is_array($value) && $this->hasRule($attribute, 'Array')) {
            foreach ($value as $element) {
                if (is_array($element)) {
                    return false;
                }
            }

            return count(array_diff($value, $parameters)) === 0;
        }

        return ! is_array($value) && in_array((string) $value, $parameters);
    }

    /**
     * Validate that the values of an attribute is in another attribute.
	 * 验证一个属性的值是否在另一个属性中
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array  $parameters
     * @return bool
     */
    public function validateInArray($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'in_array');

        $explicitPath = ValidationData::getLeadingExplicitAttributePath($parameters[0]);

        $attributeData = ValidationData::extractDataFromPath($explicitPath, $this->data);

        $otherValues = Arr::where(Arr::dot($attributeData), function ($value, $key) use ($parameters) {
            return Str::is($parameters[0], $key);
        });

        return in_array($value, $otherValues);
    }

    /**
     * Validate that an attribute is an integer.
	 * 验证属性是否为整数
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function validateInteger($attribute, $value)
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Validate that an attribute is a valid IP.
	 * 验证属性是否为有效的IP
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function validateIp($attribute, $value)
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Validate that an attribute is a valid IPv4.
	 * 验证属性是否为有效的IPv4
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function validateIpv4($attribute, $value)
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    /**
     * Validate that an attribute is a valid IPv6.
	 * 验证属性是否为有效的IPv6
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function validateIpv6($attribute, $value)
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    /**
     * Validate the attribute is a valid JSON string.
	 * 验证属性是否为有效的JSON字符串
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function validateJson($attribute, $value)
    {
        if (is_array($value)) {
            return false;
        }

        if (! is_scalar($value) && ! is_null($value) && ! method_exists($value, '__toString')) {
            return false;
        }

        json_decode($value);

        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Validate the size of an attribute is less than a maximum value.
	 * 验证属性的大小是否小于最大值
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array  $parameters
     * @return bool
     */
    public function validateMax($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'max');

        if ($value instanceof UploadedFile && ! $value->isValid()) {
            return false;
        }

        return $this->getSize($attribute, $value) <= $parameters[0];
    }

    /**
     * Validate the guessed extension of a file upload is in a set of file extensions.
	 * 验证文件上传的猜测扩展名在一组文件扩展名中
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array  $parameters
     * @return bool
     */
    public function validateMimes($attribute, $value, $parameters)
    {
        if (! $this->isValidFileInstance($value)) {
            return false;
        }

        if ($this->shouldBlockPhpUpload($value, $parameters)) {
            return false;
        }

        if (in_array('jpg', $parameters) || in_array('jpeg', $parameters)) {
            $parameters = array_unique(array_merge($parameters, ['jpg', 'jpeg']));
        }

        return $value->getPath() !== '' && in_array($value->guessExtension(), $parameters);
    }

    /**
     * Validate the MIME type of a file upload attribute is in a set of MIME types.
	 * 验证文件上传属性的MIME类型是否在一组MIME类型中
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array  $parameters
     * @return bool
     */
    public function validateMimetypes($attribute, $value, $parameters)
    {
        if (! $this->isValidFileInstance($value)) {
            return false;
        }

        if ($this->shouldBlockPhpUpload($value, $parameters)) {
            return false;
        }

        return $value->getPath() !== '' &&
                (in_array($value->getMimeType(), $parameters) ||
                 in_array(explode('/', $value->getMimeType())[0].'/*', $parameters));
    }

    /**
     * Check if PHP uploads are explicitly allowed.
	 * 检查是否明确允许PHP上传
     *
     * @param  mixed  $value
     * @param  array  $parameters
     * @return bool
     */
    protected function shouldBlockPhpUpload($value, $parameters)
    {
        if (in_array('php', $parameters)) {
            return false;
        }

        $phpExtensions = [
            'php', 'php3', 'php4', 'php5', 'phtml', 'phar',
        ];

        return ($value instanceof UploadedFile)
           ? in_array(trim(strtolower($value->getClientOriginalExtension())), $phpExtensions)
           : in_array(trim(strtolower($value->getExtension())), $phpExtensions);
    }

    /**
     * Validate the size of an attribute is greater than a minimum value.
	 * 验证属性的大小是否大于最小值
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array  $parameters
     * @return bool
     */
    public function validateMin($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'min');

        return $this->getSize($attribute, $value) >= $parameters[0];
    }

    /**
     * "Indicate" validation should pass if value is null.
	 * 如果value为空，"指示"验证应该通过。
     *
     * Always returns true, just lets us put "nullable" in rules.
     *
     * @return bool
     */
    public function validateNullable()
    {
        return true;
    }

    /**
     * Validate an attribute is not contained within a list of values.
	 * 验证属性不包含在值列表中
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array  $parameters
     * @return bool
     */
    public function validateNotIn($attribute, $value, $parameters)
    {
        return ! $this->validateIn($attribute, $value, $parameters);
    }

    /**
     * Validate that an attribute is numeric.
	 * 验证属性是否为数字
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function validateNumeric($attribute, $value)
    {
        return is_numeric($value);
    }

    /**
     * Validate that the current logged in user's password matches the given value.
	 * 验证当前登录用户的密码是否与给定值匹配
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array  $parameters
     * @return bool
     */
    protected function validatePassword($attribute, $value, $parameters)
    {
        $auth = $this->container->make('auth');
        $hasher = $this->container->make('hash');

        $guard = $auth->guard(Arr::first($parameters));

        if ($guard->guest()) {
            return false;
        }

        return $hasher->check($value, $guard->user()->getAuthPassword());
    }

    /**
     * Validate that an attribute exists even if not filled.
	 * 验证属性是否存在，即使属性没有被填充。
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function validatePresent($attribute, $value)
    {
        return Arr::has($this->data, $attribute);
    }

    /**
     * Validate that an attribute passes a regular expression check.
	 * 验证属性是否通过正则表达式检查
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array  $parameters
     * @return bool
     */
    public function validateRegex($attribute, $value, $parameters)
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }

        $this->requireParameterCount(1, $parameters, 'regex');

        return preg_match($parameters[0], $value) > 0;
    }

    /**
     * Validate that an attribute does not pass a regular expression check.
	 * 验证属性没有通过正则表达式检查
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array  $parameters
     * @return bool
     */
    public function validateNotRegex($attribute, $value, $parameters)
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }

        $this->requireParameterCount(1, $parameters, 'not_regex');

        return preg_match($parameters[0], $value) < 1;
    }

    /**
     * Validate that a required attribute exists.
	 * 验证所需的属性是否存在
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function validateRequired($attribute, $value)
    {
        if (is_null($value)) {
            return false;
        } elseif (is_string($value) && trim($value) === '') {
            return false;
        } elseif ((is_array($value) || $value instanceof Countable) && count($value) < 1) {
            return false;
        } elseif ($value instanceof File) {
            return (string) $value->getPath() !== '';
        }

        return true;
    }

    /**
     * Validate that an attribute exists when another attribute has a given value.
	 * 当另一个属性具有给定值时，验证该属性是否存在。
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  mixed  $parameters
     * @return bool
     */
    public function validateRequiredIf($attribute, $value, $parameters)
    {
        $this->requireParameterCount(2, $parameters, 'required_if');

        if (! Arr::has($this->data, $parameters[0])) {
            return true;
        }

        [$values, $other] = $this->prepareValuesAndOther($parameters);

        if (in_array($other, $values, is_bool($other) || is_null($other))) {
            return $this->validateRequired($attribute, $value);
        }

        return true;
    }

    /**
     * Indicate that an attribute should be excluded when another attribute has a given value.
	 * 指明当另一个属性具有给定值时应排除该属性
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  mixed  $parameters
     * @return bool
     */
    public function validateExcludeIf($attribute, $value, $parameters)
    {
        $this->requireParameterCount(2, $parameters, 'exclude_if');

        if (! Arr::has($this->data, $parameters[0])) {
            return true;
        }

        [$values, $other] = $this->prepareValuesAndOther($parameters);

        return ! in_array($other, $values, is_bool($other) || is_null($other));
    }

    /**
     * Indicate that an attribute should be excluded when another attribute does not have a given value.
	 * 指明当另一个属性没有给定值时应排除该属性
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  mixed  $parameters
     * @return bool
     */
    public function validateExcludeUnless($attribute, $value, $parameters)
    {
        $this->requireParameterCount(2, $parameters, 'exclude_unless');

        [$values, $other] = $this->prepareValuesAndOther($parameters);

        return in_array($other, $values, is_bool($other) || is_null($other));
    }

    /**
     * Validate that an attribute exists when another attribute does not have a given value.
	 * 当另一个属性没有给定值时，验证该属性是否存在。
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  mixed  $parameters
     * @return bool
     */
    public function validateRequiredUnless($attribute, $value, $parameters)
    {
        $this->requireParameterCount(2, $parameters, 'required_unless');

        [$values, $other] = $this->prepareValuesAndOther($parameters);

        if (! in_array($other, $values, is_bool($other) || is_null($other))) {
            return $this->validateRequired($attribute, $value);
        }

        return true;
    }

    /**
     * Prepare the values and the other value for validation.
	 * 准备这些值和另一个值以进行验证
     *
     * @param  array  $parameters
     * @return array
     */
    protected function prepareValuesAndOther($parameters)
    {
        $other = Arr::get($this->data, $parameters[0]);

        $values = array_slice($parameters, 1);

        if ($this->shouldConvertToBoolean($parameters[0]) || is_bool($other)) {
            $values = $this->convertValuesToBoolean($values);
        }

        if (is_null($other)) {
            $values = $this->convertValuesToNull($values);
        }

        return [$values, $other];
    }

    /**
     * Check if parameter should be converted to boolean.
	 * 检查parameter是否应该转换为布尔值
     *
     * @param  string  $parameter
     * @return bool
     */
    protected function shouldConvertToBoolean($parameter)
    {
        return in_array('boolean', Arr::get($this->rules, $parameter, []));
    }

    /**
     * Convert the given values to boolean if they are string "true" / "false".
	 * 如果给定的值是字符串"true" / "false"，则将其转换为布尔值。
     *
     * @param  array  $values
     * @return array
     */
    protected function convertValuesToBoolean($values)
    {
        return array_map(function ($value) {
            if ($value === 'true') {
                return true;
            } elseif ($value === 'false') {
                return false;
            }

            return $value;
        }, $values);
    }

    /**
     * Convert the given values to null if they are string "null".
	 * 如果给定的值是字符串"null"，则将其转换为null。
     *
     * @param  array  $values
     * @return array
     */
    protected function convertValuesToNull($values)
    {
        return array_map(function ($value) {
            return Str::lower($value) === 'null' ? null : $value;
        }, $values);
    }

    /**
     * Validate that an attribute exists when any other attribute exists.
	 * 当任何其他属性存在时，验证一个属性是否存在。
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  mixed  $parameters
     * @return bool
     */
    public function validateRequiredWith($attribute, $value, $parameters)
    {
        if (! $this->allFailingRequired($parameters)) {
            return $this->validateRequired($attribute, $value);
        }

        return true;
    }

    /**
     * Validate that an attribute exists when all other attributes exists.
	 * 当所有其他属性都存在时，验证一个属性是否存在。
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  mixed  $parameters
     * @return bool
     */
    public function validateRequiredWithAll($attribute, $value, $parameters)
    {
        if (! $this->anyFailingRequired($parameters)) {
            return $this->validateRequired($attribute, $value);
        }

        return true;
    }

    /**
     * Validate that an attribute exists when another attribute does not.
	 * 当另一个属性不存在时，验证一个属性是否存在。
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  mixed  $parameters
     * @return bool
     */
    public function validateRequiredWithout($attribute, $value, $parameters)
    {
        if ($this->anyFailingRequired($parameters)) {
            return $this->validateRequired($attribute, $value);
        }

        return true;
    }

    /**
     * Validate that an attribute exists when all other attributes do not.
	 * 当所有其他属性都不存在时，验证某个属性是否存在。
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  mixed  $parameters
     * @return bool
     */
    public function validateRequiredWithoutAll($attribute, $value, $parameters)
    {
        if ($this->allFailingRequired($parameters)) {
            return $this->validateRequired($attribute, $value);
        }

        return true;
    }

    /**
     * Determine if any of the given attributes fail the required test.
	 * 确定是否有任何给定属性未能通过所需的测试
     *
     * @param  array  $attributes
     * @return bool
     */
    protected function anyFailingRequired(array $attributes)
    {
        foreach ($attributes as $key) {
            if (! $this->validateRequired($key, $this->getValue($key))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if all of the given attributes fail the required test.
	 * 确定是否所有给定的属性都不能通过所需的测试
     *
     * @param  array  $attributes
     * @return bool
     */
    protected function allFailingRequired(array $attributes)
    {
        foreach ($attributes as $key) {
            if ($this->validateRequired($key, $this->getValue($key))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate that two attributes match.
	 * 验证两个属性是否匹配
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array  $parameters
     * @return bool
     */
    public function validateSame($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'same');

        $other = Arr::get($this->data, $parameters[0]);

        return $value === $other;
    }

    /**
     * Validate the size of an attribute.
	 * 验证属性的大小
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array  $parameters
     * @return bool
     */
    public function validateSize($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'size');

        return $this->getSize($attribute, $value) == $parameters[0];
    }

    /**
     * "Validate" optional attributes.
	 * "验证"可选属性
     *
     * Always returns true, just lets us put sometimes in rules.
     *
     * @return bool
     */
    public function validateSometimes()
    {
        return true;
    }

    /**
     * Validate the attribute starts with a given substring.
	 * 验证以给定子字符串开头的属性
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array  $parameters
     * @return bool
     */
    public function validateStartsWith($attribute, $value, $parameters)
    {
        return Str::startsWith($value, $parameters);
    }

    /**
     * Validate the attribute ends with a given substring.
	 * 验证以给定子字符串结尾的属性
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array  $parameters
     * @return bool
     */
    public function validateEndsWith($attribute, $value, $parameters)
    {
        return Str::endsWith($value, $parameters);
    }

    /**
     * Validate that an attribute is a string.
	 * 验证属性是否为字符串
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function validateString($attribute, $value)
    {
        return is_string($value);
    }

    /**
     * Validate that an attribute is a valid timezone.
	 * 验证属性是一个有效的时区
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function validateTimezone($attribute, $value)
    {
        try {
            new DateTimeZone($value);
        } catch (Exception $e) {
            return false;
        } catch (Throwable $e) {
            return false;
        }

        return true;
    }

    /**
     * Validate that an attribute is a valid URL.
	 * 验证属性是否为有效的URL
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function validateUrl($attribute, $value)
    {
        if (! is_string($value)) {
            return false;
        }

        /*
         * This pattern is derived from Symfony\Component\Validator\Constraints\UrlValidator (5.0.7).
         *
         * (c) Fabien Potencier <fabien@symfony.com> http://symfony.com
         */
        $pattern = '~^
            (aaa|aaas|about|acap|acct|acd|acr|adiumxtra|adt|afp|afs|aim|amss|android|appdata|apt|ark|attachment|aw|barion|beshare|bitcoin|bitcoincash|blob|bolo|browserext|calculator|callto|cap|cast|casts|chrome|chrome-extension|cid|coap|coap\+tcp|coap\+ws|coaps|coaps\+tcp|coaps\+ws|com-eventbrite-attendee|content|conti|crid|cvs|dab|data|dav|diaspora|dict|did|dis|dlna-playcontainer|dlna-playsingle|dns|dntp|dpp|drm|drop|dtn|dvb|ed2k|elsi|example|facetime|fax|feed|feedready|file|filesystem|finger|first-run-pen-experience|fish|fm|ftp|fuchsia-pkg|geo|gg|git|gizmoproject|go|gopher|graph|gtalk|h323|ham|hcap|hcp|http|https|hxxp|hxxps|hydrazone|iax|icap|icon|im|imap|info|iotdisco|ipn|ipp|ipps|irc|irc6|ircs|iris|iris\.beep|iris\.lwz|iris\.xpc|iris\.xpcs|isostore|itms|jabber|jar|jms|keyparc|lastfm|ldap|ldaps|leaptofrogans|lorawan|lvlt|magnet|mailserver|mailto|maps|market|message|mid|mms|modem|mongodb|moz|ms-access|ms-browser-extension|ms-calculator|ms-drive-to|ms-enrollment|ms-excel|ms-eyecontrolspeech|ms-gamebarservices|ms-gamingoverlay|ms-getoffice|ms-help|ms-infopath|ms-inputapp|ms-lockscreencomponent-config|ms-media-stream-id|ms-mixedrealitycapture|ms-mobileplans|ms-officeapp|ms-people|ms-project|ms-powerpoint|ms-publisher|ms-restoretabcompanion|ms-screenclip|ms-screensketch|ms-search|ms-search-repair|ms-secondary-screen-controller|ms-secondary-screen-setup|ms-settings|ms-settings-airplanemode|ms-settings-bluetooth|ms-settings-camera|ms-settings-cellular|ms-settings-cloudstorage|ms-settings-connectabledevices|ms-settings-displays-topology|ms-settings-emailandaccounts|ms-settings-language|ms-settings-location|ms-settings-lock|ms-settings-nfctransactions|ms-settings-notifications|ms-settings-power|ms-settings-privacy|ms-settings-proximity|ms-settings-screenrotation|ms-settings-wifi|ms-settings-workplace|ms-spd|ms-sttoverlay|ms-transit-to|ms-useractivityset|ms-virtualtouchpad|ms-visio|ms-walk-to|ms-whiteboard|ms-whiteboard-cmd|ms-word|msnim|msrp|msrps|mss|mtqp|mumble|mupdate|mvn|news|nfs|ni|nih|nntp|notes|ocf|oid|onenote|onenote-cmd|opaquelocktoken|openpgp4fpr|pack|palm|paparazzi|payto|pkcs11|platform|pop|pres|prospero|proxy|pwid|psyc|pttp|qb|query|redis|rediss|reload|res|resource|rmi|rsync|rtmfp|rtmp|rtsp|rtsps|rtspu|s3|secondlife|service|session|sftp|sgn|shttp|sieve|simpleledger|sip|sips|skype|smb|sms|smtp|snews|snmp|soap\.beep|soap\.beeps|soldat|spiffe|spotify|ssh|steam|stun|stuns|submit|svn|tag|teamspeak|tel|teliaeid|telnet|tftp|things|thismessage|tip|tn3270|tool|turn|turns|tv|udp|unreal|urn|ut2004|v-event|vemmi|ventrilo|videotex|vnc|view-source|wais|webcal|wpid|ws|wss|wtai|wyciwyg|xcon|xcon-userid|xfire|xmlrpc\.beep|xmlrpc\.beeps|xmpp|xri|ymsgr|z39\.50|z39\.50r|z39\.50s)://                                 # protocol
            (((?:[\_\.\pL\pN-]|%[0-9A-Fa-f]{2})+:)?((?:[\_\.\pL\pN-]|%[0-9A-Fa-f]{2})+)@)?  # basic auth
            (
                ([\pL\pN\pS\-\_\.])+(\.?([\pL\pN]|xn\-\-[\pL\pN-]+)+\.?) # a domain name
                    |                                                 # or
                \d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}                    # an IP address
                    |                                                 # or
                \[
                    (?:(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){6})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:::(?:(?:(?:[0-9a-f]{1,4})):){5})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:[0-9a-f]{1,4})))?::(?:(?:(?:[0-9a-f]{1,4})):){4})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,1}(?:(?:[0-9a-f]{1,4})))?::(?:(?:(?:[0-9a-f]{1,4})):){3})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,2}(?:(?:[0-9a-f]{1,4})))?::(?:(?:(?:[0-9a-f]{1,4})):){2})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,3}(?:(?:[0-9a-f]{1,4})))?::(?:(?:[0-9a-f]{1,4})):)(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,4}(?:(?:[0-9a-f]{1,4})))?::)(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,5}(?:(?:[0-9a-f]{1,4})))?::)(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,6}(?:(?:[0-9a-f]{1,4})))?::))))
                \]  # an IPv6 address
            )
            (:[0-9]+)?                              # a port (optional)
            (?:/ (?:[\pL\pN\-._\~!$&\'()*+,;=:@]|%[0-9A-Fa-f]{2})* )*          # a path
            (?:\? (?:[\pL\pN\-._\~!$&\'\[\]()*+,;=:@/?]|%[0-9A-Fa-f]{2})* )?   # a query (optional)
            (?:\# (?:[\pL\pN\-._\~!$&\'()*+,;=:@/?]|%[0-9A-Fa-f]{2})* )?       # a fragment (optional)
        $~ixu';

        return preg_match($pattern, $value) > 0;
    }

    /**
     * Validate that an attribute is a valid UUID.
	 * 验证属性是否为有效的UUID
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function validateUuid($attribute, $value)
    {
        return Str::isUuid($value);
    }

    /**
     * Get the size of an attribute.
	 * 得到属性的大小
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return mixed
     */
    protected function getSize($attribute, $value)
    {
        $hasNumeric = $this->hasRule($attribute, $this->numericRules);

        // This method will determine if the attribute is a number, string, or file and
        // return the proper size accordingly. If it is a number, then number itself
        // is the size. If it is a file, we take kilobytes, and for a string the
        // entire length of the string will be considered the attribute size.
		// 此方法将确定属性是数字、字符串还是文件，并相应地返回正确的大小。
		// 如果它是一个数字，那么数字本身就是大小。如果是文件，我们取千字节，
		// 对于字符串，字符串的整个长度将被视为属性大小。
        if (is_numeric($value) && $hasNumeric) {
            return $value;
        } elseif (is_array($value)) {
            return count($value);
        } elseif ($value instanceof File) {
            return $value->getSize() / 1024;
        }

        return mb_strlen($value);
    }

    /**
     * Check that the given value is a valid file instance.
	 * 检查给定的值是否为有效的文件实例
     *
     * @param  mixed  $value
     * @return bool
     */
    public function isValidFileInstance($value)
    {
        if ($value instanceof UploadedFile && ! $value->isValid()) {
            return false;
        }

        return $value instanceof File;
    }

    /**
     * Determine if a comparison passes between the given values.
	 * 确定是否在给定值之间进行比较
     *
     * @param  mixed  $first
     * @param  mixed  $second
     * @param  string  $operator
     * @return bool
     *
     * @throws \InvalidArgumentException
     */
    protected function compare($first, $second, $operator)
    {
        switch ($operator) {
            case '<':
                return $first < $second;
            case '>':
                return $first > $second;
            case '<=':
                return $first <= $second;
            case '>=':
                return $first >= $second;
            case '=':
                return $first == $second;
            default:
                throw new InvalidArgumentException;
        }
    }

    /**
     * Parse named parameters to $key => $value items.
	 * 将命名参数解析为$key => $value项
     *
     * @param  array  $parameters
     * @return array
     */
    protected function parseNamedParameters($parameters)
    {
        return array_reduce($parameters, function ($result, $item) {
            [$key, $value] = array_pad(explode('=', $item, 2), 2, null);

            $result[$key] = $value;

            return $result;
        });
    }

    /**
     * Require a certain number of parameters to be present.
	 * 要求提供一定数量的参数
     *
     * @param  int  $count
     * @param  array  $parameters
     * @param  string  $rule
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function requireParameterCount($count, $parameters, $rule)
    {
        if (count($parameters) < $count) {
            throw new InvalidArgumentException("Validation rule $rule requires at least $count parameters.");
        }
    }

    /**
     * Check if the parameters are of the same type.
	 * 检查参数类型是否一致
     *
     * @param  mixed  $first
     * @param  mixed  $second
     * @return bool
     */
    protected function isSameType($first, $second)
    {
        return gettype($first) == gettype($second);
    }

    /**
     * Adds the existing rule to the numericRules array if the attribute's value is numeric.
	 * 如果属性的值为数字，则将现有规则添加到numericRules数组中.
     *
     * @param  string  $attribute
     * @param  string  $rule
     * @return void
     */
    protected function shouldBeNumeric($attribute, $rule)
    {
        if (is_numeric($this->getValue($attribute))) {
            $this->numericRules[] = $rule;
        }
    }
}
