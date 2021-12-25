<?php

use Illuminate\Database\Seeder;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('User')->insertOrIgnore([
          ['id'=>1, 'Username'=>'root', 'Password'=>password_hash("Suankularb138", PASSWORD_DEFAULT),
          'Rank'=>0, 'DisplayName'=>'root', 'OrganizationIDList'=>json_encode([])]
        ]);
    }
}
