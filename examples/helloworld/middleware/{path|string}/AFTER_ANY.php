<?php
$request = App::current()->context();
echo "middleware ".__FILE__." executed. path: {$request->params['path']} <br/>";