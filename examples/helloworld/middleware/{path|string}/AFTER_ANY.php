<?php
$path = FoldyExampleApp1::getRequestParam('path');
FoldyExampleApp1::setResponseHeader("header2", "middleware " . __FILE__ . " executed. path: {$path}");
$content = &FoldyExampleApp1::getResponseContent();
$content ['log']  [] = "middleware " . __FILE__ . " executed. path: {$path}";
FoldyExampleApp1::getLogger()->info("middleware %s executed", __FILE__);