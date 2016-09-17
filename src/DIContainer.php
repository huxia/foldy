<?php
/**
 * Created by PhpStorm.
 * User: dashi
 * Date: 16/9/5
 * Time: 下午8:12
 */
namespace Foldy;

use Closure;

class DIContainer
{
    /**
     * @var array
     *
     * $dependencies[$name] = [
     *   "callable" => ... ,
     *   "value" => ... ,
     *   "shared" => true|false
     * ]
     */
    private $dependencies = [];

    /**
     * @param string $name
     * @param array $args
     * @return null|mixed
     */
    public function get(string $name, array $args = [])
    {
        if (!isset($this->dependencies[$name])) {
            return null;
        }
        $dependency = $this->dependencies[$name];

        assert(isset($dependency['callable']) || isset($dependency['value']));

        if (isset($dependency['callable'])) {

            $persistence = $dependency['shared'] ?? false;
            if ($persistence && isset($dependency['value'])) {
                return $dependency['value'];
            }
            $dependency['value'] = call_user_func_array($dependency['callable'], $args);
        }
        return $dependency['value'];
    }

    /**
     * @param string $name
     * @param Closure|mixed $any
     * @param bool $shared
     */
    public function set(string $name, $any, bool $shared = false)
    {
        if (is_object($any) && ($any instanceof Closure)) {
            $this->dependencies[$name] = [
                'callable' => $any,
                'shared' => $shared,
            ];
        }else{
            $this->dependencies[$name] = [
                'value' => $any
            ];
        }
    }

    public function has(string $name):bool
    {
        return isset($this->dependencies[$name]);
    }
}