<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TreeTypesTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('tree_types')->insert([
            ['name' => 'sour'],
            ['name' => 'sweet'],
            ['name' => 'semi_sweet'],
        ]);
    }
}