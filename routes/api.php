<?php

$api = app('Dingo\Api\Routing\Router');

$api->version('v1', [
    'namespace' => 'App\Http\Controllers\v1',
], function ($api) {
    $api->get('/', function () use ($api) {
        return app()->version();
    });

    $api->get('key',function(){
        return str_random(32);
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
            
            // TEMPLATE
            $api->get('/checklists/templates', ['as' => 'listallchecklisttemplate', 'uses' => 'TemplateControllers@listallchecklisttemplate']);
            $api->get('/checklists/templates/{templateId}', ['as' => 'getchecklisttemplate', 'uses' => 'TemplateControllers@getchecklisttemplate']);
            $api->post('checklists/templates', ['as' => 'createchecklisttemplate', 'uses' => 'TemplateControllers@store']);
            $api->patch('checklists/templates/{templateId}', ['as' => 'updatechecklisttemplate', 'uses' => 'TemplateControllers@update']);
            $api->delete('/checklists/templates/{templateId}', ['as' => 'deletechecklisttemplate', 'uses' => 'TemplateControllers@destroy']);

            // ITEMS
            $api->post('checklists/{checklistId}/items', ['as' => 'createchecklistitem', 'uses' => 'ItemsControllers@store']);
            $api->get('checklists/{checklistId}/items/{itemId}', ['as' => 'getchecklistitem', 'uses' => 'ItemsControllers@getchecklistitem']);
            $api->post('checklists/complete', ['as' => 'completeitems', 'uses' => 'ItemsControllers@completeitems']);
            $api->post('checklists/incomplete', ['as' => 'incompleteitems', 'uses' => 'ItemsControllers@incompleteitems']);
            $api->get('checklists/{checklistId}/items', ['as' => 'listofitemingivenchecklist', 'uses' => 'ChecklistControllers@listofitemingivenchecklist']);
            $api->patch('checklists/{checklistId}/items/{itemId}', ['as' => 'updatechecklistitem', 'uses' => 'ItemsControllers@update']);
            $api->delete('checklists/{checklistId}/items/{itemId}', ['as' => 'deletechecklistitem', 'uses' => 'ItemsControllers@destroy']);
            $api->post('checklists/{checklistId}/items/_bulk', ['as' => 'updatebulkchecklist', 'uses' => 'ItemsControllers@bulkupdate']);
            $api->get('checklists/items/summaries', ['as' => 'summaryitem', 'uses' => 'ItemsControllers@summaries']);

            // CHECKLIST
            $api->get('checklists/{checklistId}', ['as' => 'getchecklist', 'uses' => 'ChecklistControllers@getchecklist']);
            $api->patch('checklists/{checklistId}', ['as' => 'updatechecklist', 'uses' => 'ChecklistControllers@update']);
            $api->delete('checklists/{checklistId}', ['as' => 'deletechecklist', 'uses' => 'ChecklistControllers@destroy']);
            $api->post('checklists', ['as' => 'createchecklist', 'uses' => 'ChecklistControllers@store']);
            $api->get('checklists', ['as' => 'getlistofchecklists', 'uses' => 'ChecklistControllers@getlistofchecklist']);

            
        }
    );
});
