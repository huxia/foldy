<?php
require_once __DIR__ . '/../../vendor/autoload.php';

class App extends NB\App
{

}

App::launch(function (App $app) {
    $app->configFileLogger(\NB\Loggers\LoggerInterface::LEVEL_DEBUG, __DIR__.'/tmp');
    $app->configFileRouter(__DIR__ . '/api', __DIR__ . '/middleware');
});