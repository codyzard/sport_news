<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Admin
        User::create([
            'name' => 'Quản trị viên',
            'email' => "varusdamge@gmail.com",
            'email_verified_at' => now(),
            'password' => bcrypt('tuprovip123'), // password
            'remember_token' => Str::random(10),
            'role' => 1,
        ]);
        // Author
        User::create([
            'name' => 'Lê Hoàng Tú',
            'email' => "tuprovip@gmail.com",
            'email_verified_at' => now(),
            'password' => bcrypt('tuprovip123'), // password
            'remember_token' => Str::random(10),
            'role' => 0,
        ]);
    }
}
