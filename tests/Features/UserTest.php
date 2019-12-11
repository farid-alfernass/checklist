<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use App\Controllers\AuthController;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserTest extends TestCase
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

    public function testUser_can_login_with_credential()
    {
        $faker              = Faker\Factory::create();
        $email              = $this->cekEmail($faker->email);
        $password           = str_random(8);
        factory(User::class)->create([
                                        'email' => $email,
                                        'password' => app('hash')->make($password)
                                    ]);
        $this->json('post', 'api/auth/login', [
                                                'email'     => $email,
                                                'password'  => $password
                                            ]
                                        )
            ->assertResponseOk();
    }

    public function testUser_cannot_login_without_credential()
    {
        $faker              = Faker\Factory::create();
        $email              = $this->cekEmail($faker->email);
        $password           = str_random(8);
        $this->json('post', 'api/auth/login', [
                                                'email'     => $email,
                                                'password'  => $password
                                            ]
                                        )
            ->seeJson([
                    'error'   => "Email does not exist."
                ])->assertResponseStatus(400);
    }
    
}
