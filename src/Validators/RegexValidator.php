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

    protected function getValueFromMatch($value, array &$match)
    {
        return $value;
    }

    public function validate($value, bool &$is_validate)
    {
        if (!$this->regex) {
            $is_validate = false;
            return null;
        }

        $is_validate = preg_match('/^' . $this->regex . '$/', (string)$value, $match) ? true : false;
        if (!$is_validate) {
            return null;
        }
        return $this->getValueFromMatch($value, $match);
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