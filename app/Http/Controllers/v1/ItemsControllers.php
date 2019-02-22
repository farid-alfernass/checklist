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

class ItemsControllers extends Controller
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

        $checklist = Cache::remember('item.index:show:{$page}', $expireAt, function() use ($defaultPerPage,$page,$sort) {
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

    public function store(Request $request,$id)
    {
    	$time = Carbon::now('UTC')->toIso8601String();
        $data = $request->json('data')['attributes'];
        $item = new Item();
		$item->checklistId 	= $id;
		$item->description 	= $data['description'];
		$item->due 			= $data['due'];
		$item->urgency 		= $data['urgency'];	
 
        if($item->save())
        {
            $respon = $checklist::find($id);
            return response()->json([
            							'message' => 'success', 
            							'data'	=>	[
            								'type' 	=> 'checklists',
            								'id'	=> $id,
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

    public function storeComplete(Request $request)
    {
    	$time = Carbon::now('UTC')->toIso8601String();
        $data = $request->json('data');
        // $items = array();
        $status = false;
        foreach ($data as $key) {
        	$item = Item::find($key['item_id']);
        	$item->is_completed = 1;
        	$item->save();
        	$iteng = DB::table('items')
                        ->select('items.id','items.checklistId','items.is_completed')
                        ->where([['items.id','=',$key['item_id']]])
                        ->get()->toArray();
        	$items[] = array(
        			'id' => $key['item_id'],
        			'item_id' => $key['item_id'],
        			'is_completed' => (boolval($iteng[0]->is_completed) ? 'true' : 'false'),
        			'checklist_id' => $iteng[0]->checklistId
        		);
        	
        	$status = true;
        }

        if( $status == true ){
	        return response()->json([
							'message' => 'success', 
							'data'	=>	$items,
							'links' => [ 
									'self' => $request->fullUrl() 
								]
    						],201);
        }else{
            return response()->json([
                'status' => '401'
            ],500);
        }
    }

    public function storeInComplete(Request $request)
    {
    	$time = Carbon::now('UTC')->toIso8601String();
        $data = $request->json('data');
        // $items = array();
        $status = false;
        foreach ($data as $key) {
        	$item = Item::find($key['item_id']);
        	$item->is_completed = 0;
        	$item->save();
        	$iteng = DB::table('items')
                        ->select('items.id','items.checklistId','items.is_completed')
                        ->where([['items.id','=',$key['item_id']]])
                        ->get()->toArray();
        	$items[] = array(
        			'id' => $key['item_id'],
        			'item_id' => $key['item_id'],
        			'is_completed' => (boolval($iteng[0]->is_completed) ? 'true' : 'false'),
        			'checklist_id' => $iteng[0]->checklistId
        		);
        	
        	$status = true;
        }

        if( $status == true ){
	        return response()->json([
							'message' => 'success', 
							'data'	=>	$items,
							'links' => [ 
									'self' => $request->fullUrl() 
								]
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
                                        ->get()->toArray();
                            });
        // echo "<pre>";print_r($checklist);die();
        if(!empty($checklist))
        {
        	$items = DB::table('items')
                                ->select('items.id','items.description as name','items.is_completed','items.due','items.urgency','items.completed_at','items.updated_by as last_update_by','items.updated_at','items.created_at')
                                ->where([['items.checklistId','=',$id]])
                                ->orderBy('items.urgency',$sort)
                                ->limit($defaultPerPage)
                                ->offset($page)
                                ->get()->toArray();

            foreach ($items as $item) {
        		$dataItem[] = array(
        			'id'	=> $item->id,
			        'name'=> $item->name,
			        'is_completed'=> $item->is_completed,
			        'due'=> $item->due,
			        'urgency'=> $item->urgency,
			        'completed_at' => $item->completed_at,
			        'last_update_by' => $item->last_update_by,
			        'update_at' => $item->updated_at,
			        'created_at' => $item->created_at
        			);
        	}
        	

    		$data = array(
    			'type' 	=> 'checklists',
    			'id'	=> $checklist[0]->id,
    			'attributes' => [
    				'object_domain'=> $checklist[0]->object_domain,
			        'object_id'=> $checklist[0]->object_id,
			        'description'=> $checklist[0]->description,
			        'is_completed'=> $checklist[0]->is_completed,
			        'due'=> $checklist[0]->due,
			        'urgency'=> $checklist[0]->urgency,
			        'completed_at'=> $checklist[0]->completed_at,
			        'last_update_by'=> $checklist[0]->last_update_by,
			        'updated_at'=> $checklist[0]->updated_at,
			        'created_at'=> $checklist[0]->created_at,
			        'items' => $dataItem
    				]	
    			);

            return new JsonResponse([
	            'message' => 'success',
	            'data' => (array) $data,
				'links' => [ 'self' => $request->fullUrl() ]
	        ],201);	
        	
            
        }
        else
        {
        	return new JsonResponse([
            	'message' => 'gagal',
	        ],400);
        }
        
        // return response()->json(message' => 'success', 'data'=>$post],201);
    }

    public function showItem($id,$idi,Request $request)
    {
        $defaultPerPage = 1;
        $maximumPerPage = 10;
        $page           = 0;
        $sort 			= 'asc';
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
                                        ->get()->toArray();
                            });
        // echo "<pre>";print_r($checklist);die();
        if(!empty($checklist))
        {
        	$items = DB::table('items')
                                ->select('items.id','items.description as name','items.is_completed','items.due','items.urgency','items.completed_at','items.updated_by as last_update_by','items.updated_at','items.created_at')
                                ->where([['items.id','=',$idi],['items.checklistId','=',$id]])
                                ->get()->toArray();

    		$dataItem = array(
    			'id'	=> $items[0]->id,
		        'name'=> $items[0]->name,
		        'is_completed'=> $items[0]->is_completed,
		        'due'=> $items[0]->due,
		        'urgency'=> $items[0]->urgency,
		        'completed_at' => $items[0]->completed_at,
		        'last_update_by' => $items[0]->last_update_by,
		        'update_at' => $items[0]->updated_at,
		        'created_at' => $items[0]->created_at
    			);

    		$data = array(
    			'type' 	=> 'checklists',
    			'id'	=> $checklist[0]->id,
    			'attributes' => [
    				'object_domain'=> $checklist[0]->object_domain,
			        'object_id'=> $checklist[0]->object_id,
			        'description'=> $checklist[0]->description,
			        'is_completed'=> $checklist[0]->is_completed,
			        'due'=> $checklist[0]->due,
			        'urgency'=> $checklist[0]->urgency,
			        'completed_at'=> $checklist[0]->completed_at,
			        'last_update_by'=> $checklist[0]->last_update_by,
			        'updated_at'=> $checklist[0]->updated_at,
			        'created_at'=> $checklist[0]->created_at,
			        'items' => $dataItem
    				]	
    			);

            return new JsonResponse([
	            'message' => 'success',
	            'data' => (array) $data,
				'links' => [ 'self' => $request->fullUrl() ]
	        ],201);	
        	
            
        }
        else
        {
        	return new JsonResponse([
            	'message' => 'gagal',
	        ],400);
        }
        
        // return response()->json(message' => 'success', 'data'=>$post],201);
    }

    public function update(Request $request, $id,$idi){

        $item = Item::find($idi);

        if(!$item){
            return $this->error("The Checklist with {$id} doesn't exist", 404);
        }

        $time = Carbon::now('UTC')->toIso8601String();
        $data = $request->json('data')['attribute'];
       
        $item->description   		= $data['description'];
        $item->due   				= $data['due'];
        $item->urgency 				= $data['urgency'];
        
 
        if($item->save())
        {
            $respon = Item::find($idi);
            return response()->json([
            							'message' => 'success', 
            							'data'	=>	[
            								'type' 	=> 'checklists',
            								'id'	=> $idi,
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

    public function destroy($id,$idi){

        $item = Item::find($idi);

        if(!$item){
            return new JsonResponse([
		            'message' => 'Not Found',
		        ],500);
        }

        // no need to delete the comments for the current posts,
        // since we used on delete cascase on update cascase.
        // $posts->comments()->delete();
        $item->delete();
        return new JsonResponse([
		            'message' => 'success',
		        ],201);
    }

}
