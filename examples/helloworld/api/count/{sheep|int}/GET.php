<?php
$sheep = App::getInputParam('sheep');
echo ($sheep === 1 ? "$sheep sheep" : "$sheep sheeps")."<br />";