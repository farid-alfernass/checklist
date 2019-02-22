<?php

namespace App\Http\Controllers\v1;

use Illuminate\Support\Facades\DB;
use App\Models\Item;
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
    function index(Request $request)
    {
        $defaultPerPage = 1;
        $maximumPerPage = 1;
        $page           = 1;
        $sort 			= 'asc'
        $expireAt       = Carbon::now()->addDays(1);
        if($request->has('page'))
        {
            $page = (int) $request->input('page');
        }

        if($request->has('sort'))
        {
            $sort = (string) $request->input('sort');
            if ($sort == '-urgency') {
            	$sort = 'asc'
            }
        }

        if($request->has('per_page'))
        {
            if(is_numeric($request->input('per_page')))
            {
                $defaultPerPage = (int) $request->input('per_page');

                if($defaultPerPage > $maximumPerPage)
                {
                    $defaultPerPage = $maximumPerPage;
                }
            }
        }

        $items = Cache::remember("items.index:show:{$page}", $expireAt, function() use ($page) {
                        return DB::table('items')
                                    ->orderBy('tb_post_pilihan.id_post','desc')
                                    ->limit($page)
                                    ->get();
                            });

        if(!empty($items))
        {
            // return response()->json($post,200);
        	return new JsonResponse([
	            'message' => 'success',
	            'data' => (array) $items
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

    public function store(Request $request){

        $this->validateRequest($request);

        $domain_name = $request->json()->get('object_domain'),

        $user = new Item();
        $user->name     = $request->input('name');
        $user->email    = $request->input('email');
        $user->gender   = $request->input('gender');
        $user->password = app('hash')->make($password1);
        $user->status   = 1             ;

        if($user->save())
        {
            $respon = $user::find($user->id);
            return response()->json(['message' => 'success', 'data'=>$respon],201);
        }else{
            return response()->json([
                'status' => 'failed'
            ],500);
        }
    }

}
