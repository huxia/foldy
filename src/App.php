<?php
/**
 * Created by PhpStorm.
 * User: dashi
 * Date: 16/9/5
 * Time: 下午7:39
 */
namespace Foldy;

use Foldy\Data\DB;
use Foldy\Exceptions\Exception;
use Foldy\Loggers\FileLogger;
use Foldy\Loggers\LoggerInterface;
use Foldy\Routers\File\Router as FileRouter;
use Foldy\Routers\RouterInterface;

class App
{
    /**
     * @var DIContainer $di
     */
    protected $di;
    /**
     * @var bool $proceed
     */
    protected $proceed;


    /**
     * App constructor.
     * @param DIContainer $di
     */
    public final function __construct(DIContainer $di)
    {
        $this->di = $di;
    }

    /**
     * @var App $current
     */
    private static $current;

    public static function launch(callable $callable, $di = null):App
    {
        $di = $di ?? new DIContainer();
        self::$current = $app = new static($di);
        DB::setSharedDI($di);
        call_user_func($callable, $app, $di);
        $app->process();
        return $app;
    }

    public static function current():App
    {
        return self::$current;
    }

    public static function getRequest(
        string $jsme_path,
        string $format = '',
        $default_value = null,
        int $input_type = Request::INPUT_TYPE_ALL
    ) {
        return self::$current->getRequestObject()->get($jsme_path, $format, $default_value, $input_type);
    }

    public static function getRequestParam(
        string $jsme_path,
        string $format = '',
        $default_value = null
    ) {
        return self::$current->getRequestObject()->getParam($jsme_path, $format, $default_value);
    }

    public static function getRequestQuery(
        string $jsme_path,
        string $format = '',
        $default_value = null
    ) {
        return self::$current->getRequestObject()->getQuery($jsme_path, $format, $default_value);
    }

    public static function getRequestBody(
        string $jsme_path,
        string $format = '',
        $default_value = null
    ) {
        return self::$current->getRequestObject()->getBody($jsme_path, $format, $default_value);
    }

    public static function getRequestHeader(
        string $jsme_path,
        string $format = '',
        $default_value = null
    ) {
        return self::$current->getRequestObject()->getHeader($jsme_path, $format, $default_value);
    }

    public static function getRequestFile(
        string $name
    ) {
        return self::$current->getRequestObject()->getFile($name);
    }


    public static function checkRequest(
        string $jsme_path,
        string $format = '',
        string $error_message = '',
        int $input_type = Request::INPUT_TYPE_ALL
    ) {
        return self::$current->getRequestObject()->check($jsme_path, $format, $error_message, $input_type);
    }

    public static function checkRequestParam(
        string $jsme_path,
        string $format = '',
        string $error_message = ''
    ) {
        return self::$current->getRequestObject()->checkParam($jsme_path, $format, $error_message);
    }

    public static function checkRequestBody(
        string $jsme_path,
        string $format = '',
        string $error_message = ''
    ) {
        return self::$current->getRequestObject()->checkBody($jsme_path, $format, $error_message);
    }

    public static function checkRequestQuery(
        string $jsme_path,
        string $format = '',
        string $error_message = ''
    ) {
        return self::$current->getRequestObject()->checkQuery($jsme_path, $format, $error_message);
    }

    public static function checkRequestHeader(
        string $jsme_path,
        string $format = '',
        string $error_message = ''
    ) {
        return self::$current->getRequestObject()->checkHeader($jsme_path, $format, $error_message);
    }

    public static function &getResponseContent()
    {
        return self::$current->getResponseObject()->content;
    }

    public static function setResponseContent($content)
    {
        self::$current->getResponseObject()->content = $content;;
    }

    public static function getResponseContentType()
    {
        return self::$current->getResponseObject()->contentType;
    }

    public static function setResponseContentType(string $type)
    {
        self::$current->getResponseObject()->contentType = $type;
    }

    public static function getResponseHeader(string $key)
    {
        return self::$current->getResponseObject()->getHeader($key);
    }

    public static function setResponseHeader(string $key, $value)
    {
        self::$current->getResponseObject()->setHeader($key, $value);
    }

    public static function getLogger($name = ''):LoggerInterface
    {
        return self::$current->getLoggerObject($name);
    }

    public function configFileRouter($route_config, $middleware_config = null):FileRouter
    {
        $router = FileRouter::create($this->di, $route_config, $middleware_config);
        $this->di->set('router', $router);
        return $router;
    }

    public function configFileLogger(int $level, string $folder = '')
    {

        $this->di->set(Constants::DI_KEY_LOGGER, function (string $name = '') use ($level, $folder):LoggerInterface {

            if (!$name) {
                $name = Utils::getClassBaseName(static::class);
            }
            $name = trim($name);
            if (!$name) {
                $name = 'NB';
            }
            $tmp_dir = $folder ? $folder : sys_get_temp_dir();
            return FileLogger::get(rtrim($tmp_dir, '/') . '/' . $name . '.log', $level);
        });
    }

    protected function process()
    {
        if ($this->proceed) {
            throw new \Exception("this function should be called only once per request");
        }
        $this->proceed = true;
        $current_request = $this->getRequestObject();
        $current_response = $this->getResponseObject();
        $router = $this->getRouter();
        $router->process($current_request, $current_response);
    }

    public function getDI()
    {
        return $this->di;
    }

    public function getResponseObject():Response
    {

        if (!$this->di->has('response')) {

            $class_request = $this->di->get(Constants::DI_KEY_CLASS_RESPONSE) ?? Response::class;
            $response = call_user_func([$class_request, 'create'], $this->getDI());
            $this->di->set('response', $response);
            return $response;
        }
        return $this->di->get('response');
    }

    public function getRequestObject():Request
    {
        if (!$this->di->has('request')) {

            $class_request = $this->di->get(Constants::DI_KEY_CLASS_REQUEST) ?? Request::class;
            $context = call_user_func([$class_request, 'create'], $this->getDI());
            $this->di->set('request', $context);
            return $context;
        }
        return $this->di->get('request');
    }

    public function getRouter():RouterInterface
    {
        return $this->di->get('router');
    }

    public function getLoggerObject(string $name = ''):LoggerInterface
    {
        if (!$this->di->has(Constants::DI_KEY_LOGGER)) {
            throw new Exception("no logger of $name configured");
        }
        return $this->di->get(Constants::DI_KEY_LOGGER, [$name]);
    }
}