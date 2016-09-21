<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/models/User.php';
class FoldyExampleApp2 extends \Foldy\App
{

}

FoldyExampleApp2::launch(function (FoldyExampleApp2 $app, \Foldy\DIContainer $di) {
    $app->configFileLogger(\Foldy\Loggers\LoggerInterface::LEVEL_DEBUG, __DIR__ . '/tmp');
    $app->configFileRouter(__DIR__ . '/api');
    $di->set('db', array(
            'driver' => 'mysql', // Db driver
            'host' => 'localhost',
            'database' => 'test',
            'username' => 'root',
            'password' => '',
        )
    );
});