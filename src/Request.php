<?php
/**
 * Created by PhpStorm.
 * User: dashi
 * Date: 16/9/5
 * Time: 下午10:04
 */
namespace Foldy;


use Foldy\Exceptions\Exception;
use Foldy\Exceptions\InputException;
use Foldy\Validators\Validator;

class Request
{
    const INPUT_TYPE_PARAM = 1;
    const INPUT_TYPE_BODY = 2;
    const INPUT_TYPE_QUERY = 4;
    const INPUT_TYPE_HEADER = 8;
    const INPUT_TYPE_ALL = self::INPUT_TYPE_PARAM | self::INPUT_TYPE_BODY | self::INPUT_TYPE_QUERY | self::INPUT_TYPE_HEADER;

    /**
     * @var DIContainer $di
     */
    protected $di;
    public $inputMethod;

    public $schema;
    public $host;
    public $port;
    public $path;
    public $query;

    public $headers;
    /**
     * @var \stdClass
     */
    public $body;
    public $files;

    public $time;


    public $params;

    protected function __construct(DIContainer $di)
    {
        $this->di = $di;
        $this->loadFromSystemRequest();
    }

    protected function loadFromSystemRequest()
    {
        // method
        $this->inputMethod = $_SERVER['REQUEST_METHOD'];
        // schema, host, port
        if ($_SERVER['HTTPS'] ?? false && $_SERVER['HTTPS'] !== 'off') {
            $this->schema = 'https';
            list($this->host, $this->port) = explode(':', $_SERVER['HTTP_HOST'] . ':443');
        } else {
            $this->schema = 'http';
            list($this->host, $this->port) = explode(':', $_SERVER['HTTP_HOST'] . ':80');
        }
        // path
        if (isset($_SERVER['REQUEST_URI'])) {
            $this->path = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
        } else {
            $this->path = urldecode($_SERVER['SCRIPT_NAME']);
        }

        // headers
        $this->headers = getallheaders();
        // query
        parse_str($_SERVER['QUERY_STRING'] ?? parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $this->query);
        // body
        $request_content_type = $this->headers['Content-Type'] ?? '';
        if (preg_match('/^application\/json(\W.*)?$/i', $request_content_type)) {
            $raw_body = file_get_contents("php://input", null, null, null, 2 * 1024 * 1024);
            $this->body = json_decode($raw_body, false);
        } elseif (preg_match('/^application\/x-www-form-urlencoded(\W.*)?$/i', $request_content_type) && $this->inputMethod == 'PUT') {
            $raw_body = file_get_contents("php://input", null, null, null, 2 * 1024 * 1024);
            parse_str($raw_body, $body_array);
            $this->body = (object)$body_array;
        } elseif ($_POST) {
            $this->body = (object)$_POST;
        } else {
            $this->body = new \stdClass();
        }
        // files
        $this->files = $_FILES ? $_FILES : [];

        // time
        $this->time = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
    }

    public static function create(DIContainer $di):Request
    {
        return new static($di);
    }


    public function getParam(string $jsme_path, string $format, $default_value = null)
    {
        return $this->get($jsme_path, $format, $default_value, self::INPUT_TYPE_PARAM);
    }

    public function getBody(string $jsme_path, string $format, $default_value = null)
    {
        return $this->get($jsme_path, $format, $default_value, self::INPUT_TYPE_BODY);
    }

    public function getQuery(string $jsme_path, string $format, $default_value = null)
    {
        return $this->get($jsme_path, $format, $default_value, self::INPUT_TYPE_QUERY);
    }

    public function getHeader(string $jsme_path, string $format, $default_value = null)
    {
        return $this->get($jsme_path, $format, $default_value, self::INPUT_TYPE_HEADER);
    }

    /**
     * @param string $name
     * @return array|null
     */
    public function getFile(string $name)
    {
        return $this->files[$name] ?? null;
    }


    public function checkParam(string $jsme_path, string $format = '', string $error_message = '')
    {
        return $this->check($jsme_path, $format, $error_message, self::INPUT_TYPE_PARAM);
    }

    public function checkBody(string $jsme_path, string $format = '', string $error_message = '')
    {
        return $this->check($jsme_path, $format, $error_message, self::INPUT_TYPE_BODY);
    }

    public function checkQuery(string $jsme_path, string $format = '', string $error_message = '')
    {
        return $this->check($jsme_path, $format, $error_message, self::INPUT_TYPE_QUERY);
    }

    public function checkHeader(string $jsme_path, string $format = '', string $error_message = '')
    {
        return $this->check($jsme_path, $format, $error_message, self::INPUT_TYPE_HEADER);
    }

    private static $ERROR_AS_DEFAULT_VALUE = null;

    public static function inputTypeName(int $input_type):string
    {
        switch ($input_type) {
            case self::INPUT_TYPE_ALL:
                return "input";
            case self::INPUT_TYPE_BODY:
                return "body";
            case self::INPUT_TYPE_PARAM:
                return "params";
            case self::INPUT_TYPE_QUERY:
                return "query";
            case self::INPUT_TYPE_HEADER:
                return "headers";
            default:
                throw new Exception("unknown input type: $input_type");
        }
    }

    private function &returnOrThrow(&$default_value, $error_message, $jsme_path, int $input_type, string $format)
    {
        if ($default_value !== null && $default_value === self::$ERROR_AS_DEFAULT_VALUE) {
            $exp = $this->di->get(Constants::DI_KEY_EXCEPTION_CHECK_INPUT_ERROR,
                    [$error_message, $jsme_path, $input_type]) ??
                new InputException($error_message ? $error_message : ("check " . self::inputTypeName($input_type). " failed: \"$jsme_path\" ~ $format"));
            throw $exp;
        }
        return $default_value;
    }

    public function get(
        string $jsme_path,
        string $format = '',
        $default_value = null,
        int $input_type = self::INPUT_TYPE_ALL,
        string $error_message = ''
    ) {

        switch ($input_type) {
            case self::INPUT_TYPE_ALL:
                $raw_value = Utils::visit($this->params, $jsme_path) ??
                    Utils::visit($this->body, $jsme_path) ??
                    Utils::visit($this->query, $jsme_path) ??
                    Utils::visit($this->headers, $jsme_path);
                break;
            case self::INPUT_TYPE_PARAM:
                $raw_value = Utils::visit($this->params, $jsme_path);
                break;
            case self::INPUT_TYPE_BODY:
                $raw_value = Utils::visit($this->body, $jsme_path);
                break;
            case self::INPUT_TYPE_QUERY:
                $raw_value = Utils::visit($this->query, $jsme_path);
                break;
            case self::INPUT_TYPE_HEADER:
                $raw_value = Utils::visit($this->headers, $jsme_path);
                break;
            default:
                $raw_value = null;
                $matched_once = false;
                if ($input_type & self::INPUT_TYPE_PARAM){
                    $matched_once = true;
                    $raw_value = $raw_value ?? Utils::visit($this->params, $jsme_path);
                }
                if ($input_type & self::INPUT_TYPE_BODY){
                    $matched_once = true;
                    $raw_value = $raw_value ?? Utils::visit($this->body, $jsme_path);
                }
                if ($input_type & self::INPUT_TYPE_QUERY){
                    $matched_once = true;
                    $raw_value = $raw_value ?? Utils::visit($this->query, $jsme_path);
                }
                if ($input_type & self::INPUT_TYPE_HEADER){
                    $matched_once = true;
                    $raw_value = $raw_value ?? Utils::visit($this->headers, $jsme_path);
                }
                if (!$matched_once) {
                    throw new \Exception("unknown input type: $input_type");
                }
                break;
        }
        if ($raw_value === null) {
            return self::returnOrThrow($default_value, $error_message, $jsme_path, $input_type, $format);
        }
        if (!$format) {
            return $raw_value;
        }
        if (Validator::has($format)) {
            $validator = Validator::get($format);
            $is_validate = true;
            $filtered = $validator->validate($raw_value, $is_validate);
            if (!$is_validate) {
                return self::returnOrThrow($default_value, $error_message, $jsme_path, $input_type, $format);
            }
            return $filtered;
        } else {
            if (!preg_match($format, (string)$raw_value)) {
                return self::returnOrThrow($default_value, $error_message, $jsme_path, $input_type, $format);
            }
            return $raw_value;
        }
    }

    public function check(
        string $jsme_path,
        string $format = '',
        string $error_message = '',
        int $input_type = self::INPUT_TYPE_ALL
    ) {
        if (self::$ERROR_AS_DEFAULT_VALUE === null) {
            self::$ERROR_AS_DEFAULT_VALUE = self::class."::default_value";
        }
        return $this->get(
            $jsme_path,
            $format ? $format : '/^.+$/',
            self::$ERROR_AS_DEFAULT_VALUE,
            $input_type,
            $error_message);
    }
}