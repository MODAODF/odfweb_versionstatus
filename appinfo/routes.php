<?php
return [
    'routes' => [
	   ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
       ['name' => 'page#setTimeConfig', 'url' => '/setTime', 'verb' => 'GET'],
       ['name' => 'page#result', 'url' => '/result/{updateInfo}', 'verb' => 'GET'],
    ]
];
