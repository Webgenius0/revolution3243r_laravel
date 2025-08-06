<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('categories')->insert([
            [
                'name' => 'Web Design',
                'slug' => 'web-design',
                'status' => 'active',
            ],
            [
                'name' => 'Web Development',
                'slug' => 'web-development',
                'status' => 'active',
            ],
            [
                'name' => 'Web Deployment',
                'slug' => 'web-deployment',
                'status' => 'active',
            ]
        ]);
    }
}
