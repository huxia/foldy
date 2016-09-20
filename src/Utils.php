<?php
/**
 * Created by PhpStorm.
 * User: dashi
 * Date: 16/9/5
 * Time: 下午7:39
 */
namespace Foldy;

use JmesPath\Env as JmesPath;

class Utils
{
    public static function getClassBaseName(string $class_full_name):string
    {
        return preg_replace('/^.*\\\\/', '', $class_full_name);
    }

    public static function countPregGroups(string $preg):int
    {
        $len = strlen($preg);
        $count = 0;
        for ($i = 0; $i < $len; $i++) {
            if ($preg[$i] === "\\") {
                $i++;
                continue;
            }
            if ($preg[$i] === '(') {
                $count++;
            }
        }
        return $count;
    }

    public static function camelcaseToUnderlineJoined(string $str):string
    {
        return ltrim(strtolower(preg_replace('/[A-Z]/', '_$0', $str)), '_');
    }

    /**
     * visit path in object using JSMEPath
     * @param array|object $data
     * @param string $jsme_path
     * @return mixed|null
     */
    public static function visit(&$data, string $jsme_path = '')
    {
        if (!$jsme_path) {
            return $data;
        }
        return JmesPath::search($jsme_path, $data);
    }
}