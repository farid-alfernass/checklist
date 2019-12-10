<?php
use Illuminate\Database\Seeder;
class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // TEMPLATE SEEDER
        factory(App\Models\User::class, 2)->create();
        // TEMPLATE SEEDER
        factory(App\Models\Template::class, 10)->create();
        // CHECKLIST SEEDER
        factory(App\Models\Checklist::class, 10)->create();
        // ITEM SEEDER
        factory(App\Models\Item::class, 10)->create();
    }
}