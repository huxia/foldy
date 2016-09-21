<?php
require_once __DIR__ . '/../../vendor/autoload.php';

class FoldyExampleApp1 extends \Foldy\App
{

}

FoldyExampleApp1::launch(function (FoldyExampleApp1 $app) {
    $app->configFileLogger(\Foldy\Loggers\LoggerInterface::LEVEL_DEBUG, __DIR__.'/tmp');
    $app->configFileRouter(__DIR__ . '/api', __DIR__ . '/middleware');
});