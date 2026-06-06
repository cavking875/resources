<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        if (!DB::table('users')->where('email', 'admin@resourceplanner.local')->exists()) {
            DB::table('users')->insert([
                'name' => 'Admin User',
                'email' => 'admin@resourceplanner.local',
                'password' => bcrypt('password'),
                'password_hash' => password_hash('password', PASSWORD_BCRYPT),
                'active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $adminUserId = DB::table('users')->where('email', 'admin@resourceplanner.local')->value('id');

        // Admin role
        DB::table('roles')->insertOrIgnore([
            ['id' => 1, 'role_name' => 'Admin', 'description' => 'System administrator'],
        ]);

        // Link admin user to Admin role
        if ($adminUserId) {
            DB::table('user_roles')->insertOrIgnore([
                ['user_id' => $adminUserId, 'role_id' => 1],
            ]);
        }

        // Resource roles
        DB::table('resource_roles')->insertOrIgnore([
            ['id' => 1, 'role_name' => 'Project Director', 'department' => 'Management', 'active' => 1],
            ['id' => 2, 'role_name' => 'Project Planner', 'department' => 'Planning', 'active' => 1],
            ['id' => 3, 'role_name' => 'Site Agent', 'department' => 'Site', 'active' => 1],
            ['id' => 4, 'role_name' => 'Project Manager', 'department' => 'Management', 'active' => 1],
            ['id' => 5, 'role_name' => 'Commercial Manager', 'department' => 'Commercial', 'active' => 1],
            ['id' => 6, 'role_name' => 'Design Manager', 'department' => 'Design', 'active' => 1],
        ]);

        // Complexity multipliers
        DB::table('complexity_multipliers')->insertOrIgnore([
            ['complexity_level' => 'low', 'multiplier' => 0.85],
            ['complexity_level' => 'medium', 'multiplier' => 1.00],
            ['complexity_level' => 'high', 'multiplier' => 1.20],
            ['complexity_level' => 'very_high', 'multiplier' => 1.40],
        ]);

        // Phase multipliers for Project Planner (role_id=2)
        DB::table('phase_multipliers')->insertOrIgnore([
            ['role_id' => 2, 'phase_name' => 'planning', 'multiplier' => 1.50],
            ['role_id' => 2, 'phase_name' => 'construction', 'multiplier' => 1.00],
            ['role_id' => 2, 'phase_name' => 'commissioning', 'multiplier' => 0.80],
            ['role_id' => 2, 'phase_name' => 'close_out', 'multiplier' => 0.60],
        ]);
    }
}
