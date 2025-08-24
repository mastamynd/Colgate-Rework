<?php

namespace Database\Seeders;

use App\Models\Boundary;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BoundarySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        DB::table('boundaries')->truncate();

        // Use raw SQL to insert data with proper geometry handling
        DB::statement("
            INSERT INTO `boundaries` (`id`, `name`, `code`, `type`, `parent_type`, `parent_code`, `geometry`) VALUES
            (1, 'MOMBASA', 1, 'county', 'COUNTRY', '0', '{}'),
            (2, 'NAIROBI', 2, 'county', 'COUNTRY', '0', '{}'),
            (3, 'KIAMBU', 3, 'county', 'COUNTRY', '0', '{}'),
            (4, 'NAKURU', 4, 'county', 'COUNTRY', '0', '{}'),
            (5, 'KISUMU', 5, 'county', 'COUNTRY', '0', '{}'),
            (6, 'CHANGAMWE', 1, 'constituency', 'county', '1', '{}'),
            (7, 'JOMBA', 2, 'constituency', 'county', '1', '{}'),
            (8, 'KISAUNI', 3, 'constituency', 'county', '1', '{}'),
            (9, 'WESTLANDS', 4, 'constituency', 'county', '2', '{}'),
            (10, 'DAGORETTI NORTH', 5, 'constituency', 'county', '2', '{}'),
            (11, 'LANGATA', 6, 'constituency', 'county', '2', '{}'),
            (12, 'KIBRA', 7, 'constituency', 'county', '2', '{}'),
            (13, 'KIAMBU', 8, 'constituency', 'county', '3', '{}'),
            (14, 'THIKA TOWN', 9, 'constituency', 'county', '3', '{}'),
            (15, 'PTOYO/NAKWIJIT', 1, 'ward', 'constituency', '1', '{}'),
            (16, 'CHANGAMWE', 2, 'ward', 'constituency', '1', '{}'),
            (17, 'KIPEVU', 3, 'ward', 'constituency', '1', '{}'),
            (18, 'KITISURU', 4, 'ward', 'constituency', '4', '{}'),
            (19, 'PARKLANDS/HIGHRIDGE', 5, 'ward', 'constituency', '4', '{}'),
            (20, 'KANGEMI', 6, 'ward', 'constituency', '4', '{}'),
            (21, 'KAREN', 7, 'ward', 'constituency', '6', '{}'),
            (22, 'NAIROBI WEST', 8, 'ward', 'constituency', '6', '{}'),
            (23, 'MUGUMO-INI', 9, 'ward', 'constituency', '6', '{}'),
            (24, 'LAINI SABA', 10, 'ward', 'constituency', '7', '{}'),
            (25, 'LINDI', 11, 'ward', 'constituency', '7', '{}'),
            (26, 'MAKINA', 12, 'ward', 'constituency', '7', '{}')
        ");
    }
}
