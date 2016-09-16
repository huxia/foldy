<?php
/**
 * Created by PhpStorm.
 * User: dashi
 * Date: 16/9/5
 * Time: 下午7:39
 */
namespace NB;

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

    public static function getInput(
        string $jsme_path,
        string $format = '',
        $default_value = null,
        int $input_type = Context::INPUT_TYPE_ALL
    ) {
        return self::$current->context()->getInput($jsme_path, $format, $default_value, $input_type);
    }

    public static function getInputParam(
        string $jsme_path,
        string $format = '',
        $default_value = null
    ) {
        return self::$current->context()->getInputParam($jsme_path, $format, $default_value);
    }

    public static function getInputQuery(
        string $jsme_path,
        string $format = '',
        $default_value = null
    ) {
        return self::$current->context()->getInputQuery($jsme_path, $format, $default_value);
    }

    public static function getInputBody(
        string $jsme_path,
        string $format = '',
        $default_value = null
    ) {
        return self::$current->context()->getInputBody($jsme_path, $format, $default_value);
    }

    public static function getInputHeader(
        string $jsme_path,
        string $format = '',
        $default_value = null
    ) {
        return self::$current->context()->getInputHeader($jsme_path, $format, $default_value);
    }

    public static function getInputFile(
        string $name
    ) {
        return self::$current->context()->getInputFile($name);
    }


    public static function checkInput(
        string $jsme_path,
        string $format = '',
        string $error_message = '',
        int $input_type = Context::INPUT_TYPE_ALL
    ) {
        return self::$current->context()->checkInput($jsme_path, $format, $error_message, $input_type);
    }

    public static function checkInputParam(
        string $jsme_path,
        string $format = '',
        string $error_message = ''
    ) {
        return self::$current->context()->checkInputParam($jsme_path, $format, $error_message);
    }

    public static function checkInputBody(
        string $jsme_path,
        string $format = '',
        string $error_message = ''
    ) {
        return self::$current->context()->checkInputBody($jsme_path, $format, $error_message);
    }

    public static function checkInputQuery(
        string $jsme_path,
        string $format = '',
        string $error_message = ''
    ) {
        return self::$current->context()->checkInputQuery($jsme_path, $format, $error_message);
    }

    public static function checkInputHeader(
        string $jsme_path,
        string $format = '',
        string $error_message = ''
    ) {
        return self::$current->context()->checkInputHeader($jsme_path, $format, $error_message);
    }

    public function configFileRouter($route_config, $middleware_config = null):FileRouter
    {
        $router = FileRouter::create($this->di, $route_config, $middleware_config);
        $this->di->set('router', $router);
        return $router;
    }

    protected function process()
    {
        if ($this->proceed) {
            throw new \Exception("this function should be called only once per request");
        }
        $this->proceed = true;
        $current_request = $this->context();
        $router = $this->router();
        if (!$router) {
            throw new \Exception("router not configured");
        }
        $router->process($current_request);
    }

    public function di()
    {
        return $this->di;
    }

    public function context():Context
    {
        if (!$this->di->has('context')){

            $class_request = $this->di->get(Constants::DI_KEY_CLASS_CONTEXT) ?? Context::class;
            $context = call_user_func([$class_request, 'create'], $this->di());
            $this->di->set('context', $context);
            return $context;
        }
        return $this->di->get('context');
    }

    public function router():RouterInterface
    {
        return $this->di->get('router');
    }
}