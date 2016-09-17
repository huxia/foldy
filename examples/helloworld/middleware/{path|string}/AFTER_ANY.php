<?php
$path = App::getRequestParam('path');
App::setResponseHeader("header2", "middleware " . __FILE__ . " executed. path: {$path}");
$content = &App::getResponseContent();
$content ['log']  [] = "middleware " . __FILE__ . " executed. path: {$path}";
App::getLogger()->info("middleware %s executed", __FILE__);