<?php

return [
    // Task CRUD
    ['method' => 'GET',    'path' => '/tasks',            'action' => 'TaskController@index'],
    ['method' => 'GET',    'path' => '/tasks/{id}',       'action' => 'TaskController@show'],
    ['method' => 'POST',   'path' => '/tasks',            'action' => 'TaskController@store'],
    ['method' => 'PUT',    'path' => '/tasks/{id}',       'action' => 'TaskController@update'],
    ['method' => 'PUT',    'path' => '/tasks-change-status/{id}',       'action' => 'TaskController@changeStatus'],
    ['method' => 'DELETE', 'path' => '/tasks/{id}',       'action' => 'TaskController@destroy'],
];
