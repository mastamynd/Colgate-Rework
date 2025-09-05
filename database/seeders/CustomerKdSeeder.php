<?php

namespace Database\Seeders;

use App\Models\CustomerKd;
use Illuminate\Database\Seeder;

class CustomerKdSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customerKds = [
            [
                'code' => 'KD-001',
                'name' => 'Central Business District'
            ],
            [
                'code' => 'KD-002',
                'name' => 'Commercial Zone A'
            ],
            [
                'code' => 'KD-003',
                'name' => 'Residential High-End'
            ],
            [
                'code' => 'KD-004',
                'name' => 'Mixed Development'
            ],
            [
                'code' => 'KD-005',
                'name' => 'Industrial Zone'
            ],
            [
                'code' => 'KD-006',
                'name' => 'Agricultural Area'
            ],
            [
                'code' => 'KD-007',
                'name' => 'Transportation Hub'
            ],
            [
                'code' => 'KD-008',
                'name' => 'Service Center'
            ],
            [
                'code' => 'KD-009',
                'name' => 'Rural Development'
            ],
            [
                'code' => 'KD-010',
                'name' => 'Tourism Zone'
            ]
        ];

        foreach ($customerKds as $customerKd) {
            CustomerKd::create($customerKd);
        }
    }
}