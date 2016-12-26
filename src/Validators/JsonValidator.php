<?php
/**
 * Created by PhpStorm.
 * User: dashi
 * Date: 16/9/5
 * Time: 下午11:23
 */
namespace Foldy\Validators;

use Foldy\Exceptions\InputException;
use Foldy\Utils;

class JsonValidator extends Validator
{
    const TYPE_ANY = 0;
    const TYPE_JSON_OBJECT = 1;
    const TYPE_JSON_ARRAY = 2;

    const RESULT_WRONG_JSON_FORMAT = self::class . '::result_WRONG_JSON_FORMAT';

    public $type;

    private $last_validate_value = null;
    private $last_validate_json_decode_result = null;

    /**
     * @param $value
     * @return mixed|null
     * @throws InputException
     */
    protected function &jsonDecode(&$value)
    {
        if ($this->last_validate_value && $value === $this->last_validate_value) {
            return $this->last_validate_json_decode_result;
        }
        $this->last_validate_value = &$value;
        if (is_string($value)) {
            // GET URI?query=JSON
            // $value is json-encoded string
            $this->last_validate_json_decode_result = json_decode($value, false);
            if ($this->last_validate_json_decode_result === null && $value !== "null") {
                return self::RESULT_WRONG_JSON_FORMAT;
            }
        } else {
            // POST URI
            // BODY: JSON
            // $value would be directly an object
            $this->last_validate_json_decode_result = &$value;
        }

        return $this->last_validate_json_decode_result;
    }

    public function validate($value) :bool
    {
        $r = &$this->jsonDecode($value);
        if ($r === self::RESULT_WRONG_JSON_FORMAT) {
            return false;
        }
        switch ($this->type) {
            case self::TYPE_JSON_ARRAY:
                if (!is_array($r)) {
                    return false;
                }
                break;
            case self::TYPE_JSON_OBJECT:
                if (!is_object($r)) {
                    return false;
                }
                break;
        }
        return true;
    }

    public function filter($value)
    {
        return $this->jsonDecode($value);
    }

    public function __construct(int $type)
    {
        $this->type = $type;
    }

}