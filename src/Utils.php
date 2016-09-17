<?php
/**
 * Created by PhpStorm.
 * User: dashi
 * Date: 16/9/5
 * Time: 下午7:39
 */
namespace Foldy;

class Utils
{
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
}