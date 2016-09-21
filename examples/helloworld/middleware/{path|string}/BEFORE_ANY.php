<?php
$path = FoldyExampleApp1::getRequestParam('path');
FoldyExampleApp1::setResponseHeader("header1", "middleware " . __FILE__ . " executed. path: {$path}");
FoldyExampleApp1::getLogger()->info("middleware %s executed", __FILE__);