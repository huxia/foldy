<?php
$path = App::getRequestParam('path');
App::setResponseHeader("header2", "middleware " . __FILE__ . " executed. path: {$path}");
$content = &App::getResponseContent();
if ($content) {
    $content ['log']  [] = "middleware " . __FILE__ . " executed. path: {$path}";
}