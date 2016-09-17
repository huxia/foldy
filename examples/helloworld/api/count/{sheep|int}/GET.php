<?php
$sheep = App::getRequestParam('sheep');
echo ($sheep === 1 ? "$sheep sheep" : "$sheep sheeps")."<br />";