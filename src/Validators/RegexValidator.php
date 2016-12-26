<?php
/**
 * Created by PhpStorm.
 * User: dashi
 * Date: 16/9/5
 * Time: 下午11:23
 */
namespace Foldy\Validators;

class RegexValidator extends Validator
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

}