<?php

namespace App\Http\Controllers\v1;

use Illuminate\Http\Request;
use App\Models\Template;
use App\Models\Checklist;
use App\Models\Item;
use App\Models\User;
use App\Http\Resources\Template\ListAllChecklistTemplateCollection;
use App\Http\Resources\Template\CreateChecklistTemplateResource;
use Faker\Factory as Faker;
use App\Http\Controllers\Controller;
use Tymon\JWTAuth\Facades\JWTAuth;

class TemplateControllers extends Controller{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    // public function __construct()
    // {
    //     $this->middleware('auth');
    // }
    // public function __construct(){
    //     if ((App::environment() == 'testing') && array_key_exists("HTTP_AUTHORIZATION",  Request::server())) {
    //         JWTAuth::setRequest(\Route::getCurrentRequest());
    //     }
    // }

    public function listallchecklisttemplate()
    {
        try{
            $data      = Template::with('checklist')->paginate();
            if(count($data) > 0){
                return new ListAllChecklistTemplateCollection($data);
            }else{
                return response()->json(
                    [
                        'data'   => []
                    ],200
                );    
            }
        }catch(Exception $e){
            return response()->json(
                [
                    'data'      => [$e->message()]
                ], 500
            );
        }
    }

    public function getchecklisttemplate($templateId)
    {
        try{
            $data      = Template::where('id',$templateId)->with('checklist')->paginate();
            if(count($data) > 0){
                return new ListAllChecklistTemplateCollection($data);
            }else{
                return response()->json(
                    [
                        'data'   => []
                    ],200
                );    
            }
        }catch(Exception $e){
            return response()->json(
                [
                    'data'      => [$e->message()]
                ], 500
            );
        }
    }

    public function store(Request $request)
    {
        try{
            $faker                  = Faker::create();
            $req                    = json_decode($request->getContent(),true);
            $data                   = $req['data']['attributes'];
            $auth                   = $request->header();
            $token                  = $auth['authorization'];
            $user                   = json_decode(JWTAuth::toUser($token),true);
            $dataitems              = $data['items'];
            $exetemplate            = Template::create(
                                    [
                                        'name'   => $data['name']

                                    ]
            );

            $exechecklist           = $exetemplate->checklist()->create(
                                    [
                                        'description'   => $data['checklist']['description'],
                                        'due_interval'  => $data['checklist']['due_interval'],
                                        'due_unit'      => $data['checklist']['due_unit']
                                    ]
            );
            foreach($dataitems as $val){
                $exechecklist->items()->create(
                    [
                        'user_id'       => $user['id'],
                        'description'   => $val['description'],
                        'urgency'       => $val['urgency'],
                        'due_interval'  => $val['due_interval'],
                        'due_unit'      => $val['due_unit']
                    ]
                );
            }
            $datatemplate           = Template::find($exetemplate->id);
            return new CreateChecklistTemplateResource($datatemplate);
        }catch(Exception $e){
            $dataitem['status']     = 500;
            $dataitem['error']      = $e->getMessage();
            return response()->json(
                $dataitem, 500
            );
        }
    }    

    public function update(Request $request,$templateId)
    {
        // try{
            $faker                  = Faker::create();
            $req                    = json_decode($request->getContent(),true);
            $data                   = $req['data'];
            $auth                   = $request->header();
            $token                  = $auth['authorization'];
            $user                   = json_decode(JWTAuth::toUser($token),true);
            $dataitems              = $data['items'];

            $findtemplate           = Template::find($templateId)->first();
            $exetemplate            = $findtemplate->update(
                                    [
                                        'name'   => $data['name']
        
                                    ]
            );
            var_dump('Update Template: ',$exetemplate);
        
            $exechecklist           = $findtemplate->checklist()->update(
                                    [
                                        'description'   => $data['checklist']['description'],
                                        'due_interval'  => $data['checklist']['due_interval'],
                                        'due_unit'      => $data['checklist']['due_unit']
                                    ]
            );
            var_dump('Update Checklist: ',$exechecklist);
        
            $execitem               = $findtemplate->checklist()->items()->update(
                                    [
                                        'description'   => $data['items']['description'],
                                        'urgency'       => $data['items']['urgency'],
                                        'due_interval'  => $data['items']['due_interval'],
                                        'due_unit'      => $data['items']['due_unit']
                                    ]
            );
            var_dump('Create Items: ',$execitem);                    

            /*
            $findtemplate           = Template::find($templateId)->first();
            $exetemplate            = $findtemplate->update(
                                    [
                                        'name'   => $data['name']

                                    ]
            );
            var_dump($exetemplate);

            $exechecklist           = $findtemplate->checklist()->update(
                                    [
                                        'description'   => $data['checklist']['description'],
                                        'due_interval'  => $data['checklist']['due_interval'],
                                        'due_unit'      => $data['checklist']['due_unit']
                                    ]
            );
            var_dump($exechecklist);
            // foreach($dataitems as $val){
            //     $exechecklist->items()->update(
            //         [
            //             'user_id'       => $user['id'],
            //             'description'   => $val['description'],
            //             'urgency'       => $val['urgency'],
            //             'due_interval'  => $val['due_interval'],
            //             'due_unit'      => $val['due_unit']
            //         ]
            //     );
            // }
            // $datatemplate           = Template::find($exetemplate->id);
            // return new CreateChecklistTemplateResource($datatemplate);
        // }catch(\Exception $e){
        //     $dataitem['status']     = 500;
        //     $dataitem['error']      = $e->getMessage();
        //     return response()->json(
        //         $dataitem, 500
        //     );
        // }
        */
    }

    public function destroy($templateId)
    {
        try{
            $check             = Template::find($templateId);
            if($check){
                $result        = Template::find($templateId)->delete();
                if($result){
                    $dataitem['status']  = 201;
                    $dataitem['action']  = 'success';
                }
            }else{
                $dataitem['status']  = 404;
                $dataitem['error']  = 'Not Found';
            }
            return response()->json(
                $dataitem, 500
            );
        }catch(\Exception $e){
            $dataitem['status']     = 500;
            $dataitem['error']      = $e->getMessage();
            return response()->json(
                $dataitem, 500
            );
        }
    }
}
