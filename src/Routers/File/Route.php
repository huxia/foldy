<?php
/**
 * Created by PhpStorm.
 * User: dashi
 * Date: 16/9/5
 * Time: 下午9:43
 */
namespace Foldy\Routers\File;

use Foldy\Request;
use Foldy\Utils;
use Foldy\Validators\RegexValidator;
use Foldy\Validators\Validator;

class Route
{
    const PRIORITY_DEFAULT = 50;

    const TYPE_API = 0;
    const TYPE_MIDDLEWARE_PRE = 1;
    const TYPE_MIDDLEWARE_POST = 2;

    /**
     * @var int $priority
     */
    public $priority;
    /**
     * @var string $pathRegex
     */
    public $pathRegex;
    /**
     * @var array $pathValidators
     */
    public $pathVariables;
    /**
     * @var string $method
     */
    public $method;
    /**
     * @var string $phrase
     */
    public $phrase;
    /**
     * @var string $comment
     */
    public $comment;
    /**
     * @var array $conditions
     */
    public $conditions;

    /**
     * @var string $file
     */
    public $file;

    static function compare(Route $r1, Route $r2):int
    {
        return $r1->priority <=> $r2->priority;
    }

    /**
     * @param Request $request
     * @param int $type
     * @return bool|array false if not match; the matching path variable array when matching
     */
    function match(Request $request, int $type)
    {
        // detect match

        $path_match = [];

        if ($this->method && $request->inputMethod != $this->method) {
            return false;
        }
        if ($this->pathRegex && !preg_match($this->pathRegex, $request->path, $path_match)) {
            return false;
        }
        if ($this->conditions) {
            foreach ($this->conditions as $k => $v) {
                if ($v === "") {
                    // $k must exists
                    if ($request->body[$k] ?? $request->query[$k] ?? null === null) {
                        return false;
                    }
                } else {
                    if ($request->body[$k] ?? $request->query[$k] ?? null != $v) {
                        return false;
                    }
                }
            }
        }

        // prepare for params
        $params = [];
        for ($i = count($this->pathVariables) - 1; $i >= 0; $i--) {
            $path_variable = $this->pathVariables[$i];

            $variable_value = $path_match[$path_variable['group_index']];
            if (isset($path_variable['validator_name'])) {
                // validator might has extra validation function
                $validator = Validator::get($path_variable['validator_name']);
                $is_pure_regex_validator = false;
                if (is_a($validator, RegexValidator::class)) {
                    /** @var RegexValidator $validator */
                    $is_pure_regex_validator = $validator->isPureRegexValidator();
                }
                if ($is_pure_regex_validator) {
                    $filtered = $variable_value;
                } else {
                    $is_validate = true;
                    $filtered = $validator->validate($variable_value, $is_validate);
                    if (!$is_validate) {
                        return false;
                    }
                }


                $params[$path_variable['name']] = $filtered;
            } else {
                $params[$path_variable['name']] = $variable_value;
            }
        }

        return $params;
    }

    /**
     * @param string $folder
     * @param string $base_remote_path
     * @param string $path
     * @param string $filename
     * @return Route|null
     * @example $route = createFromPath('/var/project/api', '/', 'say/{word}', 'POST.php');
     */
    public static function createFromPath(string $folder, string $base_remote_path, string $path, string $filename)
    {
        // filename syntax:
        // [Priority-][Phrase_]Method[?Conditions][#Comments].php
        if (!preg_match('/
                    ^
                    (\d+\-)? # 1 Priority
                    ((BEFORE|AFTER)_)? # 2-3 Phrase
                    (ANY|GET|POST|DELETE|PUT|HEAD|OPTIONS|TRACE|CONNECT) # 4 Method
                    (\?([^\#]*))? # 5-6 Conditions
                    (\#.*?)? # 7 Comments
                    \.php 
                    $
                    /ix', $filename, $m)
        ) {
            return null;
        }


        // path example:
        // /say/hello/to/{whatever}
        // /user/{id|int}/profile
        // /user/{id~\d+}/profile

        $remote_path_regex = preg_quote(preg_replace('/#[^\/]*/', '',
            rtrim($base_remote_path, '/') . $path));

        static $variable_begin_double_regex;
        if (!$variable_begin_double_regex) {
            $variable_begin_double_regex = preg_quote(preg_quote('{'));
        }
        static $variable_end_double_regex;
        if (!$variable_end_double_regex) {
            $variable_end_double_regex = preg_quote(preg_quote('}'));
        }
        $variables = [];
        $variable_group_index = 1;


        $remote_path_regex = preg_replace_callback('/' . $variable_begin_double_regex . '([^\/]+)' . $variable_end_double_regex . '/',
            function ($matches) use (&$variables, &$variable_group_index, $path) {
                // variable syntax:
                // {Name[|Validator]}
                // {Name[~Regex]}
                $origin = stripslashes($matches[1]);
                if (preg_match('/^(\w+)(|\|(\w+))$/', $origin, $m)) {

                    $name = $m[1];
                    $validator_name = empty($m[3]) ? "uri_component" : $m[3];
                    $variables [] = [
                        "name" => $name,
                        "validator_name" => $validator_name,
                        "group_index" => $variable_group_index,
                    ];

                    $variable_regex = '(' . Validator::get($validator_name)->regex . ')';
                    $variable_group_index += Utils::countPregGroups($variable_regex);
                    return $variable_regex;
                } elseif (preg_match('/^(\w+)~(.*)$/', $origin, $m)) {

                    $name = $m[1];
                    // \ is not good for filename, so we use % to escape regex control chars (like lua)
                    // here we need to replace %w to \w and replace %% to \%
                    $variable_regex = '(' . preg_replace_callback('/%./', function ($variable_regex_escape) {
                            return '\\' . $variable_regex_escape[0][1];
                        }, $m[2]) . ')';
                    $variables [] = [
                        "name" => $name,
                        "group_index" => $variable_group_index,
                    ];

                    $variable_group_index += Utils::countPregGroups($variable_regex);
                    return $variable_regex;
                } else {
                    throw new \Exception("wrong route variable format: $origin($path)");
                }
            }, $remote_path_regex);


        $route = new Route();

        $route->pathRegex = '#^' . $remote_path_regex . '$#';
        $route->pathVariables = $variables;

        $route->priority = $m[1] ? intval($m[1]) : self::PRIORITY_DEFAULT;
        $route->phrase = $m[3] ?? null;
        $route->method = $m[4] ?? null;
        if ($route->method) {
            $route->method = strtoupper($route->method);
            if ($route->method === 'ANY') {
                $route->method = null;
            }
        }
        parse_str($m[6] ?? '', $route->conditions);
        $route->comment = $m[7] ?? '';
        $route->file = rtrim($folder, '/') . '/' . trim($path, '/') . '/' . $filename;

        return $route;
    }

}