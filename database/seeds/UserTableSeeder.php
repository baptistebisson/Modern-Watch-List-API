<?php

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            'name' => 'Jean Eude',
            'login' => 'Admin'
            'email' => 'contact@baptiste-bisson.com',
            'password' => app('hash')->make('password'),
        ]);
    }
}
