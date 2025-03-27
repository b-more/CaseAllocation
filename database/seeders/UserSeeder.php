<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $phones = ['0975020473', '0972959023', '0969893182'];

        DB::table('users')->insert([
            [
                "name" => "Namiluko",
                "role_id" => 1,
                "email" => "namiluko@frauds.hq",
                "phone" => "0977307860",
                "password" => Hash::make("Admin.1234"),
                "is_active" => 1,
            ],
            [
                "name" => "Chikuba",
                "role_id" => 1,
                "email" => "chikuba@frauds.hq",
                "phone" => "0977297601",
                "password" => Hash::make("Admin.1234"),
                "is_active" => 1,
            ],
            [
                "name" => "Akushanga",
                "role_id" => 1,
                "email" => "akushanga@frauds.hq",
                "phone" => "0979189674",
                "password" => Hash::make("Admin.1234"),
                "is_active" => 1,
            ],
            [
                "name" => "Nkombalume",
                "role_id" => 2,
                "email" => "nkombalume@frauds.hq",
                "phone" => "0977346240",
                "password" => Hash::make("Investigator1234"),
                "is_active" => 1,
            ],
            [
                "name" => "Mwale",
                "role_id" => 2,
                "email" => "mwale@frauds.hq",
                "phone" => "0977663392",
                "password" => Hash::make("Investigator1234"),
                "is_active" => 1,
            ],
            [
                "name" => "Njekwa",
                "role_id" => 2,
                "email" => "njekwa@frauds.hq",
                "phone" => "0977316474",
                "password" => Hash::make("Investigator1234"),
                "is_active" => 1,
            ],
            [
                "name" => "Ilishebo",
                "role_id" => 2,
                "email" => "ilishebo@frauds.hq",
                "phone" => "0977361255",
                "password" => Hash::make("Investigator1234"),
                "is_active" => 1,
            ],
            [
                "name" => "Mupeta",
                "role_id" => 2,
                "email" => "mupeta@frauds.hq",
                "phone" => "0977701335",
                "password" => Hash::make("Investigator1234"),
                "is_active" => 1,
            ],
            [
                "name" => "Kapasa",
                "role_id" => 2,
                "email" => "kapasa@frauds.hq",
                "phone" => "0772734589",
                "password" => Hash::make("Investigator1234"),
                "is_active" => 1,
            ],
            [
                "name" => "Moono",
                "role_id" => 2,
                "email" => "moono@frauds.hq",
                "phone" => "0979541645",
                "password" => Hash::make("Investigator1234"),
                "is_active" => 1,
            ],
            [
                "name" => "Chabushiku",
                "role_id" => 2,
                "email" => "chabushiku@frauds.hq",
                "phone" => "0978106583",
                "password" => Hash::make("Investigator1234"),
                "is_active" => 1,
            ],
            [
                "name" => "Chipango",
                "role_id" => 2,
                "email" => "chipango@frauds.hq",
                "phone" => "0974007513",
                "password" => Hash::make("Investigator1234"),
                "is_active" => 1,
            ],
            [
                "name" => "simweenda",
                "role_id" => 2,
                "email" => "simweenda@frauds.hq",
                "phone" => "0977997212",
                "password" => Hash::make("Investigator1234"),
                "is_active" => 1,
            ],
            [
                "name" => "Manda",
                "role_id" => 2,
                "email" => "manda@frauds.hq",
                "phone" => "0977474352",
                "password" => Hash::make("Investigator1234"),
                "is_active" => 1,
            ],
            [
                "name" => "Nasilele",
                "role_id" => 2,
                "email" => "nasilele@frauds.hq",
                "phone" => "0977714997",
                "password" => Hash::make("Investigator1234"),
                "is_active" => 1,
            ],
            [
                "name" => "Mubila",
                "role_id" => 2,
                "email" => "mubila@frauds.hq",
                "phone" => "0974519170",
                "password" => Hash::make("Investigator1234"),
                "is_active" => 1,
            ],
            [
                "name" => "Phiri",
                "role_id" => 2,
                "email" => "phiri@frauds.hq",
                "phone" => "0977509928",
                "password" => Hash::make("Investigator1234"),
                "is_active" => 1,
            ],
            [
                "name" => "Siyansangu",
                "role_id" => 2,
                "email" => "siyansangu@frauds.hq",
                "phone" => "0977455161",
                "password" => Hash::make("Investigator1234"),
                "is_active" => 1,
            ],
            [
                "name" => "Chinapu",
                "role_id" => 2,
                "email" => "chinapu@frauds.hq",
                "phone" => "0979494447",
                "password" => Hash::make("Investigator1234"),
                "is_active" => 1,
            ],
            [
                "name" => "Siame",
                "role_id" => 2,
                "email" => "siame@frauds.hq",
                "phone" => "0974353285",
                "password" => Hash::make("Investigator1234"),
                "is_active" => 1,
            ],
            [
                "name" => "Chilembo",
                "role_id" => 2,
                "email" => "chilembo@frauds.hq",
                "phone" => "0979125938",
                "password" => Hash::make("Investigator1234"),
                "is_active" => 1,
            ],
            [
                "name" => "Mbewe",
                "role_id" => 2,
                "email" => "mbewe@frauds.hq",
                "phone" => "0979435553",
                "password" => Hash::make("Investigator1234"),
                "is_active" => 1,
            ],
            [
                "name" => "Mwaala",
                "role_id" => 2,
                "email" => "mwaala@frauds.hq",
                "phone" => "097666891",
                "password" => Hash::make("Investigator1234"),
                "is_active" => 1,
            ],
            [
                "name" => "Mulenga",
                "role_id" => 2,
                "email" => "mulenga@frauds.hq",
                "phone" => "0975020473",
                "password" => Hash::make("Investigator1234"),
                "is_active" => 1,
            ],
            [
                "name" => "Victoria",
                "role_id" => 2,
                "email" => "mulungwizi@frauds.hq",
                "phone" => "0972944354",
                "password" => Hash::make("Investigator1234"),
                "is_active" => 1,
            ]
        ]);
    }
}
