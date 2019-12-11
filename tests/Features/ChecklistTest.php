<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use App\Controllers\AuthController;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Template;
use App\Models\Checklist;
use App\Models\Item;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\Checklist\GetChecklistResource;
use App\Http\Resources\Checklist\GetListofChecklistCollection;
use App\Helper;
use Tymon\JWTAuth\Facades\JWTAuth;

class ChecklistTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    
    public function cekEmail($email){
        $faker              = Faker\Factory::create();
        if(User::where('email',$email)->count()>0){
            $this->cekEmail($faker->email);            
        }{
            return $email;
        }
    }

    public function testGet_checklist()
    {
        try{
            $faker              = Faker\Factory::create();
            $email              = $this->cekEmail($faker->email);
            $password           = $faker->password;
            $user               = factory(User::class)->create([
                                            'email' => $email,
                                            'password' => app('hash')->make($password)
                                        ]);
            $token              = JWTAuth::fromUser($user);

            $count_item             = Template::count();
            if($count_item>0){
                $template_id        = Template::get()->last()->id+1;
            }else{
                $template_id        = 1;
            }

            $count_item             = Checklist::count();
            if($count_item>0){
                $checklist_id       = Checklist::get()->last()->id+1;
            }else{
                $checklist_id       = 1;
            }

            $count_item             = Item::count();
            if($count_item>0){
                $item_id            = Item::get()->last()->id+1;
            }else{
                $item_id            = 1;
            }
            $is_completed           = $faker->boolean;

            factory(Template::class)->create(
                [
                    'id'    => $template_id
                ]
            );

            factory(Checklist::class)->create(
                [
                    'id'            => $checklist_id,
                    'template_id'   => $template_id
                ]
            );

            factory(Item::class)->create(
                [
                    'id'            => $item_id,
                    'checklist_id'  => $checklist_id
                ]
            );

            $data               = new GetChecklistResource(Checklist::where('id',$checklist_id)->first());
            $testCase           = json_encode($data);

            $status             = (array) $this->call(
                                'GET',
                                '/api/checklists/'.$checklist_id,
                                [],
                                [],
                                [],
                                $headers = [
                                    'HTTP_Authorization' => 'bearer '.$token,
                                    'CONTENT_TYPE' => 'application/json',
                                    'HTTP_ACCEPT' => 'application/json'
                                ]
                                
                                );
                                
            $result             = $this->response->getContent();
            $helper             = new Helper;
            if($helper->isJson($result)){
                
                $this->assertJsonStringEqualsJsonString(
                    $testCase, $result
                );
            }else{
                throw new Exception('Responses: '.$status);
            }

            User::destroy($user->id);   
            Template::destroy($template_id);
        }catch(\Exception $e){
            $this->expectException($e->getMessage());
        }
    }

    public function testUpdate_checklist()
    {
        try{
            $faker              = Faker\Factory::create();
            $email              = $this->cekEmail($faker->email);
            $password           = $faker->password;
            $user               = factory(User::class)->create([
                                            'email' => $email,
                                            'password' => app('hash')->make($password)
                                        ]);
            $token              = JWTAuth::fromUser($user);

            $count_item             = Template::count();
            if($count_item>0){
                $template_id        = Template::get()->last()->id+1;
            }else{
                $template_id        = 1;
            }

            $count_item             = Checklist::count();
            if($count_item>0){
                $checklist_id       = Checklist::get()->last()->id+1;
            }else{
                $checklist_id       = 1;
            }

            $count_item             = Item::count();
            if($count_item>0){
                $item_id            = Item::get()->last()->id+1;
            }else{
                $item_id            = 1;
            }
            $is_completed           = $faker->boolean;

            factory(Template::class)->create(
                [
                    'id'    => $template_id
                ]
            );

            factory(Checklist::class)->create(
                [
                    'id'            => $checklist_id,
                    'template_id'   => $template_id
                ]
            );

            factory(Item::class)->create(
                [
                    'id'            => $item_id,
                    'checklist_id'  => $checklist_id
                ]
            );

            $data               = new GetChecklistResource(Checklist::where('id',$checklist_id)->first());
            $testCase           = json_encode($data);

            $testParam          = [
                                    "type"      => "checklists",
                                    "id"        => $checklist_id,
                                    "attributes"    => [
                                        "object_domain"     => $faker->jobTitle,
                                        "object_id"         => $faker->randomDigit,
                                        "description"       => $faker->sentence($nbWords = 6, $variableNbWords = true),
                                        "is_completed"      => $faker->boolean,
                                        "completed_at"      => $faker->date('Y-m-d H:i:s'),
                                        "created_at"        => $faker->date('Y-m-d H:i:s')
                                    ],
                                    "links"     => [
                                        'self' => url('checklists/'.$checklist_id)
                                        ]
                                ];

            $status             = (array) $this->call(
                                'GET',
                                '/api/checklists/'.$checklist_id,
                                [],
                                [],
                                [],
                                $headers = [
                                    'HTTP_Authorization' => 'bearer '.$token,
                                    'CONTENT_TYPE' => 'application/json',
                                    'HTTP_ACCEPT' => 'application/json'
                                ]
                                );
                                
            $result             = $this->response->getContent();
            $helper             = new Helper;
            if($helper->isJson($result)){
                
                $this->assertJsonStringEqualsJsonString(
                    $testCase, $result
                );
            }else{
                throw new Exception('Responses: '.$status);
            }

            User::destroy($user->id);   
            Template::destroy($template_id);
        }catch(\Exception $e){
            $this->expectException($e->getMessage());
        }
    }

    public function testDelete_checklist()
    {
        try{
            $faker              = Faker\Factory::create();
            $email              = $this->cekEmail($faker->email);
            $password           = $faker->password;
            $user               = factory(User::class)->create([
                                            'email' => $email,
                                            'password' => app('hash')->make($password)
                                        ]);
            $token              = JWTAuth::fromUser($user);

            $count_item             = Template::count();
            if($count_item>0){
                $template_id        = Template::get()->last()->id+1;
            }else{
                $template_id        = 1;
            }

            $count_item             = Checklist::count();
            if($count_item>0){
                $checklist_id       = Checklist::get()->last()->id+1;
            }else{
                $checklist_id       = 1;
            }

            $count_item             = Item::count();
            if($count_item>0){
                $item_id            = Item::get()->last()->id+1;
            }else{
                $item_id            = 1;
            }
            $is_completed           = $faker->boolean;

            factory(Template::class)->create(
                [
                    'id'    => $template_id
                ]
            );

            factory(Checklist::class)->create(
                [
                    'id'            => $checklist_id,
                    'template_id'   => $template_id
                ]
            );

            factory(Item::class)->create(
                [
                    'id'            => $item_id,
                    'checklist_id'  => $checklist_id
                ]
            );

            $data               = [
                                    "status"    => 201,
                                    "action"    => "success"
                                ];
            $testCase           = json_encode($data);

            $status             = (array) $this->call(
                                'DELETE',
                                '/api/checklists/'.$checklist_id,
                                [],
                                [],
                                [],
                                $headers = [
                                    'HTTP_Authorization' => 'bearer '.$token,
                                    'CONTENT_TYPE' => 'application/json',
                                    'HTTP_ACCEPT' => 'application/json'
                                ]
                                );

            $result             = $this->response->getContent();
            $helper             = new Helper;
            if($helper->isJson($result)){
                $this->assertJsonStringEqualsJsonString(
                    $testCase, $result
                );
            }else{
                throw new Exception('Responses: '.$status);
            }

            User::destroy($user->id);   
            Template::destroy($template_id);
        }catch(\Exception $e){
            $this->expectException($e->getMessage());
        }
    }    

    public function testCreate_checklist()
    {
        try{
            $faker              = Faker\Factory::create();
            $email              = $this->cekEmail($faker->email);
            $password           = $faker->password;
            $user               = factory(User::class)->create([
                                            'email' => $email,
                                            'password' => app('hash')->make($password)
                                        ]);
            $token              = JWTAuth::fromUser($user);

            $count_item             = Template::count();
            if($count_item>0){
                $template_id        = Template::get()->last()->id+1;
            }else{
                $template_id        = 1;
            }

            $count_item             = Checklist::count();
            if($count_item>0){
                $checklist_id       = Checklist::get()->last()->id+1;
            }else{
                $checklist_id       = 1;
            }

            factory(Template::class)->create(
                [
                    'id'    => $template_id
                ]
            );

            $testParam          = [
                                    "attributes"    => [
                                        "object_domain" => $faker->jobTitle,
                                        "object_id"     => $faker->randomDigit,
                                        "due"           => $faker->date('Y-m-d H:i:s'),
                                        "urgency"       => $faker->randomDigit,
                                        "description"   => $faker->sentence($nbWords = 6, $variableNbWords = true),
                                        "items"         => [
                                            $faker->sentence($nbWords = 6, $variableNbWords = true),
                                            $faker->sentence($nbWords = 6, $variableNbWords = true),
                                            $faker->sentence($nbWords = 6, $variableNbWords = true)
                                        ],
                                        "task_id"       => $faker->randomDigit
                                    ]
                                ];

            $status             = (array) $this->call(
                                'POST',
                                '/api/checklists',
                                [],
                                [],
                                [],
                                $headers = [
                                    'HTTP_Authorization' => 'bearer '.$token,
                                    'CONTENT_TYPE' => 'application/json',
                                    'HTTP_ACCEPT' => 'application/json'
                                ],
                                $json = json_encode(['data' => $testParam])
                                );
                                
            $data               = new GetChecklistResource(Checklist::all()->last());
            $testCase           = json_encode($data);
            
            $result             = $this->response->getContent();
            $helper             = new Helper;
            if($helper->isJson($result)){
                $this->assertJsonStringEqualsJsonString(
                    $testCase, $result
                );
            }else{
                throw new Exception('Responses: '.$status);
            }

            User::destroy($user->id);   
            Template::destroy($template_id);
        }catch(\Exception $e){
            $this->expectException($e->getMessage());
        }
    }
    
    public function testGet_list_of_checklist()
    {
        try{
            $faker              = Faker\Factory::create();
            $email              = $this->cekEmail($faker->email);
            $password           = $faker->password;
            $user               = factory(User::class)->create([
                                            'email' => $email,
                                            'password' => app('hash')->make($password)
                                        ]);
            $token              = JWTAuth::fromUser($user);

            $data               = new GetListofChecklistCollection(Checklist::paginate());
            $testCase           = json_encode($data);
            
            $status             = (array) $this->call(
                                'GET',
                                '/api/checklists',
                                [],
                                [],
                                [],
                                [
                                    'HTTP_Authorization' => 'Bearer' . $token
                                ]
                                );
                                
            
            $result             = json_decode($this->response->getContent(),true);
            
            unset($result['links'],$result['meta']);
            $result             = json_encode($result, true);
            $helper             = new Helper;
            if($helper->isJson($result)){
                $this->assertJsonStringEqualsJsonString(
                    $testCase, $result
                );
            }else{
                throw new Exception('Responses: '.$status);
            }

            User::destroy($user->id);   
        }catch(\Exception $e){
            $this->expectException($e->getMessage());
        }
    }    
}