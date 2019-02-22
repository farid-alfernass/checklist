<?php

namespace App\Http\Controllers\v1;

use Illuminate\Support\Facades\DB;
use App\Models\Item;
use App\Models\Checklist;
use Carbon\Carbon;
use Dingo\Api\Transformer\Adapter\Fractal;
use Faker\Provider\Image;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Prophecy\Doubler\CachedDoubler;
use Laravel\Lumen\Application;
use Illuminate\Http\JsonResponse;
use Cache;
use Illuminate\Support\Facades\Redis;

class ChecklistControllers extends Controller
{
    public function index(Request $request)
    {
        $defaultPerPage = 1;
        $maximumPerPage = 10;
        $page           = 0;
        $sort 			= 'asc';
        $include 		= '';
        $expireAt       = Carbon::now()->addMinutes(3);

        if($request->has('sort'))
        {
            $sort = (string) $request->query('sort');
            if ($sort == '-urgency') {
            	$sort = 'desc';
            }
        }

        if($request->has('include'))
        {
            $include = (string) $request->query('include');
        }

        if($request->has('page_limit'))
        {
            if(is_numeric($request->query('page_limit')))
            {
                $defaultPerPage = (int) $request->query('page_limit');

                if($defaultPerPage > $maximumPerPage)
                {
                    $defaultPerPage = $maximumPerPage;
                }
            }
        }

        if($request->has('page_offset'))
        {
            $page = (int)$defaultPerPage * ( $request->query('page_offset') - 1);
        }

        $checklist = Cache::remember('checklist.index:show:{$page}', $expireAt, function() use ($defaultPerPage,$page,$sort) {
                        return DB::table('checklist')
                                        ->select('checklist.id','checklist.object_domain','checklist.object_id','checklist.description','checklist.is_completed','checklist.due','checklist.urgency','checklist.completed_at','checklist.updated_by as last_update_by','checklist.updated_at','checklist.created_at')
                                        ->orderBy('checklist.urgency',$sort)
                                        ->limit($defaultPerPage)
                                        ->offset($page)
                                        ->get()->toArray();
                            });

        if(!empty($checklist))
        {
        	foreach ($checklist as $list) {
        		$data = array(
        			'type' 	=> 'checklists',
        			'id'	=> $list->id,
        			'attributes' => [
        				'object_domain'=> $list->object_domain,
				        'object_id'=> $list->object_id,
				        'description'=> $list->description,
				        'is_completed'=> $list->is_completed,
				        'due'=> $list->due,
				        'urgency'=> $list->urgency,
				        'completed_at'=> $list->completed_at,
				        'last_update_by'=> $list->last_update_by,
				        'updated_at'=> $list->updated_at,
				        'created_at'=> $list->created_at
        				]	
        			);
        	}
        	if($request->has('include')){
        		if($include != ''){
        			$items = DB::table('items')
                                        ->select('items.id','items.description','items.is_completed','items.due','items.urgency')
                                        ->orderBy('items.urgency',$sort)
                                        ->limit($defaultPerPage)
                                        ->offset($page)
                                        ->get()->toArray();

                    foreach ($items as $item) {
		        		$dataItem = array(
		        			'type' 	=> 'item',
		        			'id'	=> $item->id,
		        			'attributes' => [
						        'description'=> $item->description,
						        'is_completed'=> $item->is_completed,
						        'due'=> $item->due,
						        'urgency'=> $item->urgency
		        				]	
		        			);
		        	}
                    return new JsonResponse([
			            'message' => 'success',
			            'meta'	=> [
			            	'count' => count($items),
			            	'total'	=> count($checklist)
			            	],
			            'data' => (array) $data,
			            'items' =>(array) $dataItem,
						'links' => [ 'self' => $request->fullUrl() ]
			        ],201);	
        		}
        	}else{
        		return new JsonResponse([
		            'message' => 'success',
		            'meta'	=> [
		            	'count' => $defaultPerPage,
		            	'total'	=> count($checklist)
		            	],
		            'data' => (array) $data,
					'links' => [ 'self' => $request->fullUrl() ]
		        ],201);	
        	}
            
        }
        else
        {
        	return new JsonResponse([
            	'message' => 'gagal',
	        ],400);
        }
        
        // return response()->json(message' => 'success', 'data'=>$post],201);
    }

    public function store(Request $request)
    {
    	$time = Carbon::now('UTC')->toIso8601String();
        $data = $request->json('data')['attributes'];
        $checklist = new Checklist();
        $checklist->object_domain     	= $data['object_domain'];
        $checklist->object_id    		= $data['object_id'];
        $checklist->due   				= $data['due'];
        $checklist->urgency 			= $data['urgency'];
        $checklist->description   		= $data['description'];
 
        if($checklist->save())
        {
        	//save to item
	        $items = $data['items'];
	        if (count($items) > 1) {
	        	foreach ($items as $key) {
	        		$item = new Item();
	        		$item->checklistId 	= $checklist->id;
	        		$item->description 	= $key;
	        		$item->due 			= $data['due'];
	        		$item->urgency 		= $data['urgency'];	
	        		$item->save();
	        	}
	        }
            $respon = $checklist::find($checklist->id);
            return response()->json([
            							'message' => 'success', 
            							'data'	=>	[
            								'type' 	=> 'checklists',
            								'id'	=> $checklist->id,
            								'attributes' => $respon
            								],
            							'links' => [ 'self' => $request->fullUrl() ]
            							
            						],201);
        }else{
            return response()->json([
                'status' => '401'
            ],500);
        }
    }

    public function show($id,Request $request)
    {
        $defaultPerPage = 1;
        $maximumPerPage = 10;
        $page           = 0;
        $sort 			= 'asc';
        $include 		= '';
        $expireAt       = Carbon::now()->addMinutes(3);

        if($request->has('sort'))
        {
            $sort = (string) $request->query('sort');
            if ($sort == '-urgency') {
            	$sort = 'desc';
            }
        }

        if($request->has('include'))
        {
            $include = (string) $request->query('include');
        }

        if($request->has('page_limit'))
        {
            if(is_numeric($request->query('page_limit')))
            {
                $defaultPerPage = (int) $request->query('page_limit');

                if($defaultPerPage > $maximumPerPage)
                {
                    $defaultPerPage = $maximumPerPage;
                }
            }
        }

        if($request->has('page_offset'))
        {
            $page = (int)$defaultPerPage * ( $request->query('page_offset') - 1);
        }

        $checklist = Cache::remember('checklist.index:show:{$id}:{$page}', $expireAt, function() use ($defaultPerPage,$page,$sort,$id) {
                        return DB::table('checklist')
                                        ->select('checklist.id','checklist.object_domain','checklist.object_id','checklist.description','checklist.is_completed','checklist.due','checklist.urgency','checklist.completed_at','checklist.updated_by as last_update_by','checklist.updated_at','checklist.created_at')
                                        ->where([['checklist.id','=',$id]])
                                        ->orderBy('checklist.urgency',$sort)
                                        ->limit($defaultPerPage)
                                        ->offset($page)
                                        ->get()->toArray();
                            });

        if(!empty($checklist))
        {
        	foreach ($checklist as $list) {
        		$data = array(
        			'type' 	=> 'checklists',
        			'id'	=> $list->id,
        			'attributes' => [
        				'object_domain'=> $list->object_domain,
				        'object_id'=> $list->object_id,
				        'description'=> $list->description,
				        'is_completed'=> $list->is_completed,
				        'due'=> $list->due,
				        'urgency'=> $list->urgency,
				        'completed_at'=> $list->completed_at,
				        'last_update_by'=> $list->last_update_by,
				        'updated_at'=> $list->updated_at,
				        'created_at'=> $list->created_at
        				]	
        			);
        	}
        	if($request->has('include')){
        		if($include != ''){
        			$items = DB::table('items')
                                        ->select('items.id','items.description','items.is_completed','items.due','items.urgency')
                                        ->where([['items.checklistId','=',$id]])
                                        ->orderBy('items.urgency',$sort)
                                        ->limit($defaultPerPage)
                                        ->offset($page)
                                        ->get()->toArray();

                    foreach ($items as $item) {
		        		$dataItem = array(
		        			'type' 	=> 'item',
		        			'id'	=> $item->id,
		        			'attributes' => [
						        'description'=> $item->description,
						        'is_completed'=> $item->is_completed,
						        'due'=> $item->due,
						        'urgency'=> $item->urgency
		        				]	
		        			);
		        	}
                    return new JsonResponse([
			            'message' => 'success',
			            'meta'	=> [
			            	'count' => count($items),
			            	'total'	=> count($checklist)
			            	],
			            'data' => (array) $data,
			            'items' =>(array) $dataItem,
						'links' => [ 'self' => $request->fullUrl() ]
			        ],201);	
        		}
        	}else{
        		return new JsonResponse([
		            'message' => 'success',
		            'meta'	=> [
		            	'count' => $defaultPerPage,
		            	'total'	=> count($checklist)
		            	],
		            'data' => (array) $data,
					'links' => [ 'self' => $request->fullUrl() ]
		        ],201);	
        	}
            
        }
        else
        {
        	return new JsonResponse([
            	'message' => 'gagal',
	        ],400);
        }
        
        // return response()->json(message' => 'success', 'data'=>$post],201);
    }

    public function update(Request $request, $id){

        $checklist = Checklist::find($id);

        if(!$checklist){
            return $this->error("The Checklist with {$id} doesn't exist", 404);
        }

        $time = Carbon::now('UTC')->toIso8601String();
        $data = $request->json('data')['attributes'];
       
        $checklist->object_domain     	= $data['object_domain'];
        $checklist->object_id    		= $data['object_id'];
        $checklist->due   				= $data['due'];
        $checklist->urgency 			= $data['urgency'];
        $checklist->description   		= $data['description'];
 
        if($checklist->save())
        {
        	//save to item
	        $items = $data['items'];
	        if (count($items) > 1) {
	        	foreach ($items as $key) {
	        		$item = new Item();
	        		$item->checklistId 	= $checklist->id;
	        		$item->description 	= $key;
	        		$item->due 			= $data['due'];
	        		$item->urgency 		= $data['urgency'];	
	        		$item->save();
	        	}
	        }
            $respon = $checklist::find($checklist->id);
            return response()->json([
            							'message' => 'success', 
            							'data'	=>	[
            								'type' 	=> 'checklists',
            								'id'	=> $checklist->id,
            								'attributes' => $respon
            								],
            							'links' => [ 'self' => $request->fullUrl() ]
            							
            						],201);
        }else{
            return response()->json([
                'status' => '401'
            ],500);
        }
    }

    public function destroy($id){

        $checklist = Checklist::find($id);

        if(!$checklist){
            return new JsonResponse([
		            'message' => 'Not Found',
		        ],500);
        }

        // no need to delete the comments for the current posts,
        // since we used on delete cascase on update cascase.
        // $posts->comments()->delete();
        if($checklist->delete()){
        	DB::table('items')->where('checklistId', '=', $id)->delete();
        }
        return new JsonResponse([
		            'message' => 'success',
		        ],201);
    }

}
