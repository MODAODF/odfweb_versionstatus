<?php
return [
    'routes' => [
	   ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
       ['name' => 'page#result', 'url' => '/', 'verb' => 'POST'],
       ['name' => 'page#sendMail', 'url' => '/mail', 'verb' => 'POST'],
    ]
];
