<?php
use Foldy\Data\DB;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/models/User.php';
class App extends \Foldy\App
{

}

App::launch(function (App $app, \Foldy\DIContainer $di) {
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