<?php
namespace NB\Routers;

use NB\Request;
use NB\Response;

/**
 * Created by PhpStorm.
 * User: dashi
 * Date: 16/9/16
 * Time: 上午3:36
 */
interface RouterInterface{
    public function process(Request $request, Response $response);
}