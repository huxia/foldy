<?php
$sheep = FoldyExampleApp1::getRequestParam('sheep');
return [
    'there_is' => $sheep === 1 ? "$sheep sheep" : "$sheep sheeps",
];