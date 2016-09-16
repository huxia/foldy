<?php
namespace NB\Routers;

use NB\Context;

/**
 * Created by PhpStorm.
 * User: dashi
 * Date: 16/9/16
 * Time: 上午3:36
 */
interface RouterInterface{
    public function process(Context $context);
}