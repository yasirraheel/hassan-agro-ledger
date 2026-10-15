<?php

namespace Database\Seeders;

use App\Models\State;
use Illuminate\Database\Seeder;

class StateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $states = array(
            array('id' => '1', 'country_id' => '21', 'name' => 'Dhaka', 'status' => '1', 'created_at' => '2026-02-15 10:00:03', 'updated_at' => '2026-02-15 10:00:03'),
            array('id' => '2', 'country_id' => '21', 'name' => 'Gazipur', 'status' => '1', 'created_at' => '2026-02-15 10:00:16', 'updated_at' => '2026-02-15 10:00:16'),
            array('id' => '3', 'country_id' => '12', 'name' => 'California', 'status' => '1', 'created_at' => '2026-02-15 10:01:26', 'updated_at' => '2026-02-15 10:01:26')
        );

        State::insert($states);
    }
}
