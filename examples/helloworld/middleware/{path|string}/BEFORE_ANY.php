<?php
$path = App::getRequestParam('path');
App::setResponseHeader("header1", "middleware " . __FILE__ . " executed. path: {$path}");