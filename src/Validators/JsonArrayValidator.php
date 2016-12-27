<?php
/**
 * Created by PhpStorm.
 * User: dashi
 * Date: 16/9/5
 * Time: 下午11:23
 */
namespace Foldy\Validators;

class JsonArrayValidator extends JsonValidator
{

    public $valueValidator;
    public $options;
    const OPTIONS_LENGTH_MIN = 'lengthMin';
    const OPTIONS_LENGTH_MAX = 'lengthMax';


    public function __construct(Validator $value_validator, array $options = [])
    {
        parent::__construct(self::TYPE_JSON_ARRAY);
        $this->valueValidator = $value_validator;
        $this->options = $options;
    }

    public function validate($value, bool &$is_validate)
    {
        $array = parent::validate($value, $is_validate);
        if (!$is_validate) {
            return null;
        }
        if (isset($this->options[self::OPTIONS_LENGTH_MIN]) && count($array) < $this->options[self::OPTIONS_LENGTH_MIN]) {
            return false;
        }
        if (isset($this->options[self::OPTIONS_LENGTH_MAX]) && count($array) >= $this->options[self::OPTIONS_LENGTH_MAX]) {
            return false;
        }


        $result = [];
        foreach ($array as $o) {
            $filtered = $this->valueValidator->validate($o, $is_validate);
            if (!$is_validate) {
                return null;
            }
            $result [] = $filtered;
        }
        return $result;
    }
}