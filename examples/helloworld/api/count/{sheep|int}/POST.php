<?php
$sheep_count = FoldyExampleApp1::getRequestParam('sheep');
$meta = FoldyExampleApp1::getRequest('meta', 'json_object', (object)["color" => "white"]);
$sheeps = [];
if ($sheep_count > 0) {
    for ($i = 0; $i < $sheep_count; $i++) {
        $sheep = [
            "index" => $i,
        ];
        $sheep['meta'] = $meta;
        $sheeps []= $sheep;
    }
}

return [
    'sheeps' => $sheeps
];