<?php
/**
 * Created by PhpStorm.
 * User: dashi
 * Date: 16/9/5
 * Time: 下午7:39
 */
namespace NB;

use NB\Loggers\FileLogger;
use NB\Loggers\LoggerInterface;
use NB\Routers\File\Router as FileRouter;
use NB\Routers\RouterInterface;

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
     * @var int $fileLoggerLevel
     */
    protected $fileLoggerLevel = LoggerInterface::LEVEL_DEBUG;
    /**
     * @var string $fileLoggerFolder
     */
    protected $fileLoggerFolder = '';

    /**
     * App constructor.
     * @param DIContainer|null $di
     */
    public final function __construct($di = null)
    {
        $this->di = $di ?? new DIContainer();
    }

    /**
     * @var App $current
     */
    private static $current;

    public static function launch(callable $callable, $di = null):App
    {
        self::$current = $app = new static($di);
        call_user_func($callable, $app);
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
        return self::$current->request()->get($jsme_path, $format, $default_value, $input_type);
    }

    public static function getRequestParam(
        string $jsme_path,
        string $format = '',
        $default_value = null
    ) {
        return self::$current->request()->getParam($jsme_path, $format, $default_value);
    }

    public static function getRequestQuery(
        string $jsme_path,
        string $format = '',
        $default_value = null
    ) {
        return self::$current->request()->getQuery($jsme_path, $format, $default_value);
    }

    public static function getRequestBody(
        string $jsme_path,
        string $format = '',
        $default_value = null
    ) {
        return self::$current->request()->getBody($jsme_path, $format, $default_value);
    }

    public static function getRequestHeader(
        string $jsme_path,
        string $format = '',
        $default_value = null
    ) {
        return self::$current->request()->getHeader($jsme_path, $format, $default_value);
    }

    public static function getRequestFile(
        string $name
    ) {
        return self::$current->request()->getFile($name);
    }


    public static function checkRequest(
        string $jsme_path,
        string $format = '',
        string $error_message = '',
        int $input_type = Request::INPUT_TYPE_ALL
    ) {
        return self::$current->request()->check($jsme_path, $format, $error_message, $input_type);
    }

    public static function checkRequestParam(
        string $jsme_path,
        string $format = '',
        string $error_message = ''
    ) {
        return self::$current->request()->checkParam($jsme_path, $format, $error_message);
    }

    public static function checkRequestBody(
        string $jsme_path,
        string $format = '',
        string $error_message = ''
    ) {
        return self::$current->request()->checkBody($jsme_path, $format, $error_message);
    }

    public static function checkRequestQuery(
        string $jsme_path,
        string $format = '',
        string $error_message = ''
    ) {
        return self::$current->request()->checkQuery($jsme_path, $format, $error_message);
    }

    public static function checkRequestHeader(
        string $jsme_path,
        string $format = '',
        string $error_message = ''
    ) {
        return self::$current->request()->checkHeader($jsme_path, $format, $error_message);
    }

    public static function &getResponseContent()
    {
        return self::$current->response()->content;
    }

    public static function setResponseContent($content)
    {
        self::$current->response()->content = $content;;
    }

    public static function getResponseContentType()
    {
        return self::$current->response()->contentType;
    }

    public static function setResponseContentType(string $type)
    {
        self::$current->response()->contentType = $type;
    }

    public static function getResponseHeader(string $key)
    {
        return self::$current->response()->getHeader($key);
    }

    public static function setResponseHeader(string $key, $value)
    {
        self::$current->response()->setHeader($key, $value);
    }

    public static function getLogger($name = ''):LoggerInterface
    {
        return self::$current->logger($name);
    }

    public function configFileRouter($route_config, $middleware_config = null):FileRouter
    {
        $router = FileRouter::create($this->di, $route_config, $middleware_config);
        $this->di->set('router', $router);
        return $router;
    }

    public function configFileLogger(int $level, string $folder)
    {
        $this->fileLoggerLevel = $level;
        $this->fileLoggerFolder = $folder;
    }

    protected function process()
    {
        if ($this->proceed) {
            throw new \Exception("this function should be called only once per request");
        }
        $this->proceed = true;
        $current_request = $this->request();
        $current_response = $this->response();
        $router = $this->router();
        $router->process($current_request, $current_response);
    }

    public function di()
    {
        return $this->di;
    }

    public function response():Response
    {

        if (!$this->di->has('response')) {

            $class_request = $this->di->get(Constants::DI_KEY_CLASS_RESPONSE) ?? Response::class;
            $response = call_user_func([$class_request, 'create'], $this->di());
            $this->di->set('response', $response);
            return $response;
        }
        return $this->di->get('response');
    }

    public function request():Request
    {
        if (!$this->di->has('request')) {

            $class_request = $this->di->get(Constants::DI_KEY_CLASS_REQUEST) ?? Request::class;
            $context = call_user_func([$class_request, 'create'], $this->di());
            $this->di->set('request', $context);
            return $context;
        }
        return $this->di->get('request');
    }

    public function router():RouterInterface
    {
        return $this->di->get('router');
    }

    public function logger(string $name = ''):LoggerInterface
    {
        if (!$this->di->has(Constants::DI_KEY_LOGGER)) {
            $this->di->set(Constants::DI_KEY_LOGGER, function ($name):LoggerInterface {

                if (!$name) {
                    $name = preg_replace('/^.*\\\\/', '', static::class);
                }
                $name = trim($name);
                if (!$name) {
                    $name = 'NB';
                }
                $tmp_dir = $this->fileLoggerFolder ? $this->fileLoggerFolder : sys_get_temp_dir();
                return FileLogger::get(rtrim($tmp_dir, '/') . '/' . $name . '.log', $this->fileLoggerLevel);
            });
        }
        return $this->di->get(Constants::DI_KEY_LOGGER, [$name]);
    }
}