<?php
/**
 * Created by PhpStorm.
 * User: dashi
 * Date: 16/9/5
 * Time: 下午9:43
 */
namespace NB\Routers\File;

use NB\Constants;
use NB\DIContainer;
use NB\Context;
use NB\Routers\RouterInterface;

class Router implements RouterInterface
{
    /**
     * @var DIContainer $di
     */
    protected $di;
    private $routes = [];
    private $middlewares = [];
    private $notFoundRoute;
    private $errorRoute;

    protected function __construct(DIContainer $di)
    {
        $this->di = $di;
    }

    /**
     * @param string $type API | MIDDLEWARE
     * @param string $path
     * @param string $base_remote_path
     * @throws \Exception
     * @return array
     */
    private function collect(string $type, string $path, string $base_remote_path = '/')
    {
        $result = [];
        $this->doCollect($path, $base_remote_path, '/', $result);
        if ($type === 'API') {
            $result = array_values(array_filter($result, function(Route $r){
                return $r->phrase ? false : true;
            }));
        } elseif ($type === 'MIDDLEWARE') {
            $result = array_values(array_filter($result, function(Route $r){
                return $r->phrase ? true : false;
            }));
        } else {
            throw new \Exception("unexpected type: $type");
        }
        return $result;
    }

    private function doCollect(string $folder, string $base_remote_path = '/', string $path, array &$result)
    {
        // for testability
        $func_scandir = $this->di->get(Constants::DI_KEY_FUNC_SCANDIR) ?? 'scandir';
        $func_is_dir = $this->di->get(Constants::DI_KEY_FUNC_IS_DIR) ?? 'is_dir';

        foreach (call_user_func($func_scandir, $folder . $path) as $filename) {
            if (substr($filename, 0, 1) == '.') {
                continue;
            }
            $new_path = rtrim($path, '/') . '/' . $filename;

            if (call_user_func($func_is_dir, $folder . $new_path)) {
                $this->doCollect($folder, $base_remote_path, $new_path, $result);
            } else {
                $route = Route::createFromPath($folder, $base_remote_path, $path, $filename);
                if ($route) {
                    $result[] = $route;
                }
            }
        }
    }
    public static function create(DIContainer $di, $route_config, $middleware_config = null):self
    {
        $result = new self($di);

        // route
        if (is_string($route_config)) {
            $result->routes = $result->collect('API', $route_config, '/');
        } elseif (is_array($route_config)) {
            $result->routes = [];
            foreach ($route_config as $remote_path => $folder) {
                if (!is_string($folder) || !is_string($remote_path)) {
                    throw new \Exception("unknown route_config value: " . json_encode($route_config));
                }
                $result->routes = array_merge($result->routes, $result->collect('API', $folder, $remote_path));
            }
        } else {
            throw new \Exception("unknown route_config type: " . gettype($route_config));
        }
        usort($result->routes, [Route::class, "compare"]);

        // middleware
        if (is_string($middleware_config)) {
            $result->middlewares = $result->collect('MIDDLEWARE', $middleware_config, '/');
        } elseif (is_array($middleware_config)) {
            $result->middlewares = [];
            foreach ($middleware_config as $remote_path => $folder) {
                if (!is_string($folder) || !is_string($remote_path)) {
                    throw new \Exception("unknown route_config value: " . json_encode($middleware_config));
                }
                $result->middlewares = array_merge($result->middlewares,
                    $result->collect('MIDDLEWARE', $folder, $remote_path));
            }
        } elseif ($middleware_config) {
            throw new \Exception("unknown route_config type: " . gettype($middleware_config));
        }

        return $result;
    }


    public function setNotFound(string $path):self
    {
        $this->notFoundRoute = new Route();
        $this->notFoundRoute->file = $path;
        return $this;
    }

    public function setError(string $path):self
    {
        $this->errorRoute = new Route();
        $this->errorRoute = $path;
        return $this;
    }
    private function doExecuteFile(string $file){
        $include_func = $this->di->get(Constants::DI_KEY_FUNC_INCLUDE) ?? 'include';
        if ($include_func === 'include'){
            // system include
            /** @noinspection PhpIncludeInspection */
            return include($file);
        }else{
            return call_user_func($include_func, $file);
        }
    }
    private function executeRoute(Context $context, Route $route, array $params = [])
    {
        $context->params = $params;

        if (in_array($route, [$this->notFoundRoute, $this->errorRoute])) {
            // no try catch
            return $this->doExecuteFile($route->file);
        } else {
            try {
                return $this->doExecuteFile($route->file);
            } catch (\Throwable $e) {
                // TODO log
                if ($this->errorRoute) {
                    return $this->executeRoute($context, $this->errorRoute, ['error' => $e, 'route' => $route]);
                } else {
                    throw $e;
                }
            }
        }
    }

    public function process(Context $context)
    {
        // apply all BEFORE_ middleware route
        foreach ($this->middlewares as $r) {
            /** @var Route $r */
            if ($r->phrase !== 'BEFORE') {
                continue;
            }
            $params = $r->match($context, Route::TYPE_MIDDLEWARE_PRE);
            if ($params === false) {
                continue;
            }
            $this->executeRoute($context, $r, $params);
        }
        // find first api route
        /** @var Route|null $api_route */
        $api_route = null;
        $api_route_params = null;
        foreach ($this->routes as $r) {
            /** @var Route $r */

            $params = $r->match($context, Route::TYPE_API);
            if ($params === false) {
                continue;
            }

            $api_route_params = $params;
            $api_route = $r;
            break;
        }

        if (!$api_route) {
            if (!$this->notFoundRoute) {
                throw new \Exception("Not Found");
            } else {
                $api_route_result = $this->executeRoute($context, $this->notFoundRoute);
            }
        } else {
            $api_route_result = $this->executeRoute($context, $api_route, $api_route_params);
        }

        // apply all AFTER_ middleware route
        foreach ($this->middlewares as $r) {
            /** @var Route $r */
            if ($r->phrase !== 'AFTER') {
                continue;
            }
            $params = $r->match($context, Route::TYPE_MIDDLEWARE_POST);
            if ($params === false) {
                continue;
            }
            $this->executeRoute($context, $r, $params);
        }

    }
}