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
    // function index(Request $request)
    // {
    //     $defaultPerPage = 1;
    //     $maximumPerPage = 1;
    //     $page           = 1;
    //     $sort 			= 'asc'
    //     $expireAt       = Carbon::now()->addDays(1);
    //     if($request->has('page'))
    //     {
    //         $page = (int) $request->input('page');
    //     }

    //     if($request->has('sort'))
    //     {
    //         $sort = (string) $request->input('sort');
    //         if ($sort == '-urgency') {
    //         	$sort = 'asc'
    //         }
    //     }

    //     if($request->has('per_page'))
    //     {
    //         if(is_numeric($request->input('per_page')))
    //         {
    //             $defaultPerPage = (int) $request->input('per_page');

    //             if($defaultPerPage > $maximumPerPage)
    //             {
    //                 $defaultPerPage = $maximumPerPage;
    //             }
    //         }
    //     }

    //     $items = Cache::remember("items.index:show:{$page}", $expireAt, function() use ($page) {
    //                     return DB::table('items')
    //                                 ->orderBy('tb_post_pilihan.id_post','desc')
    //                                 ->limit($page)
    //                                 ->get();
    //                         });

    //     if(!empty($items))
    //     {
    //         // return response()->json($post,200);
    //     	return new JsonResponse([
	   //          'message' => 'success',
	   //          'data' => (array) $items
	   //      ],201);	
    //     }
    //     else
    //     {
    //     	return new JsonResponse([
    //         	'message' => 'gagal',
	   //      ],400);
    //     }
        
    //     // return response()->json(message' => 'success', 'data'=>$post],201);
    // }

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
  //       $checklist->created_at 	= $time;
		// $checklist->updated_at 	= $time;
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
	        		// $item->created_at 	= $time;
	        		// $item->updated_at 	= $time;
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

}
