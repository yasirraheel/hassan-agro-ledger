<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = array(
            array('business_id' => '1','name' => 'Main Branch','phone' => '01712022529','email' => 'shopowner@acnoo.com','address' => 'Dhaka, Bangladesh','description' => NULL,'status' => '1','is_main' => '1','branchOpeningBalance' => '0','branchRemainingBalance' => '0','deleted_at' => NULL,'created_at' => '2026-02-24 10:07:20','updated_at' => '2026-02-24 10:07:20'),
            array('business_id' => '1','name' => 'Acnoo','phone' => '0156454','email' => 'acnoo@gmail.com','address' => 'Dhaka','description' => NULL,'status' => '1','is_main' => '0','branchOpeningBalance' => '1000','branchRemainingBalance' => '1000','deleted_at' => NULL,'created_at' => '2026-02-24 10:07:20','updated_at' => '2026-02-24 10:07:20')
        );

        DB::table('branches')->insert($branches);
    }
}
