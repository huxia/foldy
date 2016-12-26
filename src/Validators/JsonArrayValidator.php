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

    public function validate($value) :bool
    {
        if (!parent::validate($value)) {
            return false;
        }
        $array = &$this->jsonDecode($value);
        if (isset($this->options[self::OPTIONS_LENGTH_MIN]) && count($array) < $this->options[self::OPTIONS_LENGTH_MIN]){
            return false;
        }
        if (isset($this->options[self::OPTIONS_LENGTH_MAX]) && count($array) >= $this->options[self::OPTIONS_LENGTH_MAX]){
            return false;
        }


        foreach ($array as $o) {
            if (!$this->valueValidator->validate($o)) {
                return false;
            }

        }
        return true;
    }

    public function filter($value)
    {
        $result = [];
        $rs = &$this->jsonDecode($value);
        foreach ($rs as $r) {
            $result [] = $this->valueValidator->filter($r);
        }
        return $result;
    }
}