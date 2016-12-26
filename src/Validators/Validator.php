<?php
/**
 * Created by PhpStorm.
 * User: dashi
 * Date: 16/9/5
 * Time: 下午11:23
 */
namespace Foldy\Validators;

abstract class Validator
{
    public function validate($value) :bool
    {
        return false;
    }

    public function filter($value)
    {
        return $value;
    }

    /**
     * Validator constructor.
     * @param string $regex
     */
    public function __construct()
    {
    }

    private static $validators = null;

    static final function &allValidators():array
    {

        if (!self::$validators) {
            self::$validators = [
                "string" => new RegexValidator('.*'),
                "uri_component" => new RegexValidator('[^\/]+'),
                "word" => new RegexValidator('[\-\w]+'),
                "int" => new class('\-?\d+') extends RegexValidator
                {
                    public function filter($value)
                    {
                        return intval($value);
                    }

                    public function validate($value) :bool
                    {
                        if (is_int($value)) {
                            return true;
                        }
                        return parent::validate($value);
                    }
                },
                "float" => new class('\-?\d+\.?\d*') extends RegexValidator
                {

                    public function filter($value)
                    {
                        return floatval($value);
                    }

                    public function validate($value) :bool
                    {
                        if (is_int($value) || is_float($value)) {
                            return true;
                        }
                        return parent::validate($value);
                    }
                },
                "alphanum" => new RegexValidator('[a-zA-Z0-9]+'),
                "email" => new class('.+@.+') extends RegexValidator
                {
                    public function validate($value) :bool
                    {
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            return false;
                        }
                        return true;
                    }
                },
                "json" => new JsonValidator(JsonValidator::TYPE_ANY),
                "json_array" => new JsonValidator(JsonValidator::TYPE_JSON_ARRAY),
                "json_object" => new JsonValidator(JsonValidator::TYPE_JSON_OBJECT),
            ];
        }
        return self::$validators;
    }

    public static final function add(string $name, $validator):Validator
    {
        if (is_string($validator)) {
            $result = new RegexValidator($validator);
        } elseif (is_object($validator) && is_a($validator, self::class)) {
            $result = $validator;
        } else {
            throw new \Exception("not a validator: " . (is_object($validator) ? get_class($validator) : gettype($validator)));
        }
        self::allValidators()[$name] = $result;
        return $result;
    }

    public static final function has(string $name):bool
    {
        return isset(self::allValidators()[$name]);
    }

    public static final function get(string $name):Validator
    {
        return self::allValidators()[$name];
    }
}