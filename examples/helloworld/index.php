<?php
require_once __DIR__ . '/../../vendor/autoload.php';

class App extends NB\App
{

}

App::launch(function (App $app) {
    $app->configFileRouter(__DIR__ . '/api', __DIR__ . '/middleware');
});