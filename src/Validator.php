<?php
/**
 * Created by PhpStorm.
 * User: dashi
 * Date: 16/9/5
 * Time: 下午11:23
 */
namespace NB;

class Validator
{
    public $regex;

    public function validate($value) :bool
    {
        if (!$this->regex) {
            return false;
        }

        return preg_match('/^' . $this->regex . '$/', (string)$value) ? true : false;
    }

    public function filter($value)
    {
        return $value;
    }

    public function isPureRegexValidator()
    {
        return get_class($this) == self::class;
    }

    /**
     * Validator constructor.
     * @param string $regex
     */
    public function __construct(string $regex)
    {
        $this->regex = $regex;
    }

    static $validators = null;

    static function &allValidators():array
    {

        if (!self::$validators) {
            self::$validators = [
                "string" => new Validator('.+'),
                "uri_component" => new Validator('[^\/]+'),
                "word" => new Validator('[\-\w]+'),
                "int" => new class('\-?\d+') extends Validator
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
                "float" => new class('\-?\d+\.?\d*') extends Validator
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
                "alphanum" => new Validator('[a-zA-Z0-9]+'),
                "email" => new class('.+@.+') extends Validator
                {
                    public function validate($value) :bool
                    {
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            return false;
                        }
                        return true;
                    }
                },
            ];
        }
        return self::$validators;
    }

    public static function add(string $name, $validator):Validator
    {
        if (is_string($validator)) {
            $result = new Validator(($validator));
        } elseif (is_object($validator) && is_a($validator, self::class)) {
            $result = $validator;
        } else {
            throw new \Exception("not a validator: " . (is_object($validator) ? get_class($validator) : gettype($validator)));
        }
        self::allValidators()[$name] = $result;
        return $result;
    }

    public static function has(string $name):bool
    {
        return isset(self::allValidators()[$name]);
    }

    public static function get(string $name):Validator
    {
        return self::allValidators()[$name];
    }
}