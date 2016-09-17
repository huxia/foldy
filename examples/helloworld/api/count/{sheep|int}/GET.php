<?php
$sheep = App::getRequestParam('sheep');
return [
    'there_is' => $sheep === 1 ? "$sheep sheep" : "$sheep sheeps",
];