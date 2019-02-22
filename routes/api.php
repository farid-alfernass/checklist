<?php

$api = app('Dingo\Api\Routing\Router');

$api->version('v1', [
    'namespace' => 'App\Http\Controllers\v1',
], function ($api) {
    $api->get('/', function () use ($api) {
        return "hello World";
    });
    $api->post(
        'auth/login', 
        [
           'uses' => 'AuthController@authenticate'
        ]
    );
    $api->group(
        ['middleware' => 'jwt.auth'], 
        function() use ($api) {
            $api->get('users', function() {
                $users = \App\Models\User::all();
                return response()->json($users);
            });
        }
    );
    $api->post('/register', 'AuthController@register');
    $api->post('/login', 'AuthController@login');
    $api->get('/me', 'AuthController@me');
    $api->get('/refresh', 'AuthController@refresh');

    $api->post('/storage', 'StorageController@index');

    $api->get('/mentor/favorite', 'MentorController@favorite');
    $api->get('/schedule', 'ScheduleController@index');

    $api->get('/activity', 'ActivityController@main');

    $api->post('/schedule/store', 'ScheduleController@store');
});
