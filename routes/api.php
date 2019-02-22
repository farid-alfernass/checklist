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
            //checklist items
            $api->post('checklist/templates', ['uses' => 'TemplateControllers@store']);
            // $api->post('checklist/templates/{id}/assigns', ['uses' => 'TemplateControllers@storeComplete']);
            // $api->patch('checklist/templates/{id}', ['uses' => 'TemplateControllers@update']);
            // $api->delete('checklist/templates/{id}', ['uses' => 'TemplateControllers@destroy']);
            // $api->get('checklist/templates/{id}', ['uses' => 'TemplateControllers@show']);
            $api->get('checklist/templates', ['uses' => 'TemplateControllers@index']);
            
            //checklist
            $api->post('checklist', ['uses' => 'ChecklistControllers@store']);
            $api->patch('checklist/{id}', ['uses' => 'ChecklistControllers@update']);
            $api->delete('checklist/{id}', ['uses' => 'ChecklistControllers@destroy']);
            $api->get('checklist', ['uses' => 'ChecklistControllers@index']);
            $api->get('checklist/{id}', ['uses' => 'ChecklistControllers@show']);

            //checklist items
            $api->post('checklist/{id}/items', ['uses' => 'ItemsControllers@store']);
            $api->post('checklist/complete', ['uses' => 'ItemsControllers@storeComplete']);
            $api->post('checklist/incomplete', ['uses' => 'ItemsControllers@storeInComplete']);
            $api->patch('checklist/{id}/items/{idi}', ['uses' => 'ItemsControllers@update']);
            $api->delete('checklist/{id}/items/{idi}', ['uses' => 'ItemsControllers@destroy']);
            $api->get('checklist/{id}/items', ['uses' => 'ItemsControllers@show']);
            $api->get('checklist/{id}/items/{idi}', ['uses' => 'ItemsControllers@showItem']);

            
        }
    );
});
