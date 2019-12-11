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
use App\Http\Resources\Template\ListAllChecklistTemplateCollection;
use App\Http\Resources\Template\CreateChecklistTemplateResource;
use App\Helper;
use Tymon\JWTAuth\Facades\JWTAuth;

class TemplateTest extends TestCase
{

    public function cekEmail($email){
        $faker              = Faker\Factory::create();
        if(User::where('email',$email)->count()>0){
            $this->cekEmail($faker->email);            
        }{
            return $email;
        }
    }

    public function testGet_list_of_checklist()
    {
        try{
            //Generate User for Login
            $faker              = Faker\Factory::create();
            $email              = $this->cekEmail($faker->email);
            $password           = $faker->password;
            $user               = factory(User::class)->create([
                                            'email' => $email,
                                            'password' => app('hash')->make($password)
                                        ]);
            $token              = JWTAuth::fromUser($user);
            
            $data               = new ListAllChecklistTemplateCollection(Template::with('checklist')->paginate());
            $testCase           = json_encode($data);
            
            $status             =  $this->call(
                                    'GET',
                                    '/api/checklists/templates',
                                    [],
                                    [],
                                    [],
                                    [
                                        'HTTP_Authorization' => 'Bearer' . $token
                                    ]
                                );
                                
            
            //Converting data from json to Array
            // $result             = json_decode($this->response->getContent(),true);
            // echo "<pre>";print_r($result);die();
            $result             = json_decode($this->response->getContent(),true);
            unset($result['links'],$result['meta']);
            $result             = json_encode($result, true);
            $helper             = new Helper;
            if($helper->isJson($result)){
                // Testing
                $this->assertJsonStringEqualsJsonString(
                    $testCase, $result
                );
            }else{
                throw new Exception('Responses: '.$status);
            }

            // Deleting Testing DAta
            User::destroy($user->id);   
        }catch(\Exception $e){
            $this->expectException($e->getMessage());
        }
    }

    public function testCreate_checklist()
    {
        try{
            //Generate User for Login
            $faker              = Faker\Factory::create();
            $email              = $this->cekEmail($faker->email);
            $password           = $faker->password;
            $user               = factory(User::class)->create([
                                            'email' => $email,
                                            'password' => app('hash')->make($password)
                                        ]);
            $token              = JWTAuth::fromUser($user);

            $testParam          = [
                                    "attributes"    => [
                                        "name"          => $faker->name,
                                        "checklist"     => [
                                            "description"       => $faker->sentence($nbWords = 6, $variableNbWords = true),
                                            "due_interval"      => $faker->randomDigit,
                                            "due_unit"          => $faker->randomElement(['minute','hour','day','week','month'])
                                        ],
                                        "items"         => [
                                            [
                                                "description"       => $faker->sentence($nbWords = 6, $variableNbWords = true),
                                                "urgency"           => $faker->randomDigit,
                                                "due_interval"      => $faker->randomDigit,
                                                "due_unit"          => $faker->randomElement(['minute','hour','day','week','month'])    
                                            ]
                                        ]
                                    ]
                                ];

            $status             = (array) $this->call(
                                'POST',
                                '/api/checklists/templates',
                                [],
                                [],
                                [],
                                $headers = [
                                    'HTTP_Authorization' => 'bearer '.$token,
                                ],
                                json_encode(['data' => $testParam])
                                );
                                
            $data               = new CreateChecklistTemplateResource(Template::all()->last());
            $testCase           = json_encode($data);
            
            $result             = $this->response->getContent();
            $helper             = new Helper;
            if($helper->isJson($result)){
                // Testing
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

    public function testGet_checklist_template()
    {
        try{
            //Generate User for Login
            $faker              = Faker\Factory::create();
            $email              = $this->cekEmail($faker->email);
            $password           = $faker->password;
            $user               = factory(User::class)->create([
                                            'email' => $email,
                                            'password' => app('hash')->make($password)
                                        ]);
            $token              = JWTAuth::fromUser($user);
            $templateId         = Template::first()->id;
            
            $data               = new ListAllChecklistTemplateCollection(Template::where('id',$templateId)->with('checklist')->paginate());
            $testCase           = json_encode($data);
            
            $status             = (array) $this->call(
                                'GET',
                                '/api/checklists/templates/'.$templateId,
                                [],
                                [],
                                [],
                                $headers = [
                                    'HTTP_Authorization' => 'bearer '.$token,
                                    'CONTENT_TYPE' => 'application/json',
                                    'HTTP_ACCEPT' => 'application/json'
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

    public function testDelete_checklist_template()
    {
        try{
            //Generate User for Login
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
                                '/api/checklists/templates/'.$template_id,
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
}