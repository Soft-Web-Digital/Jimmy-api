<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // User::factory()
        //     ->count(10)
        //     ->create();

            // 'sannidavidsmar@gmail.com',
            //     'softwebdigital@gmail.com',
            //     'johnogunmosu@gmail.com',
            //     'jamesoluwabukola87@gmail.com',
            //     'adewuyiyusuf@yahoo.com',
            //     'animashauntaofiq@gmail.com',
            //     'support@softwebdigital.com',
            //     'john.homies@yahoo.com',
            //     'yusufadewuyi2@gmail.com',
            //     'john@softwebdigital.com'

            $users = [
                'sannidavidsmart@gmail.com',
                'softwebdigital@gmail.com',
                'johnogunmosu@gmail.com',
                'jamesoluwabukola87@gmail.com',
                'adewuyiyusuf@yahoo.com',
                'animashauntaofiq@gmail.com',
                'support@softwebdigital.com',
                'john.homies@yahoo.com',
                'yusufadewuyi2@gmail.com',
                'john@softwebdigital.com',
            ];

            // $firstname = [
            //     'Sanni',
            //     'Soft',
            //     'Ogunmosu',
            //     'Oluwabukola',
            //     'Adewuyi',
            //     'Animashaun',
            //     'Support',
            //     'John',
            //     'Yusuf',
            //     'John',
            // ];

            // $lastname = [
            //     'David',
            //     'Digital',
            //     'John',
            //     'James',
            //     'Yusuf',
            //     'Taofiq',
            //     'Softweb',
            //     'Homies',
            //     'Yusuf',
            //     'Softweb',
            // ];
    
            foreach ($users as $email) {
                if (!User::where('email', $email)->exists()) {
                    User::factory()->create([
                        'email' => $email,
                        // 'firstname' => $firstname,
                        // 'lastname' => $lastname,
                        'password' => bcrypt('password'),
                    ]);
                }
            }
    }
}
