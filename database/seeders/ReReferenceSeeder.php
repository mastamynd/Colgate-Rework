<?php

namespace Database\Seeders;

use App\Models\ReReference;
use Illuminate\Database\Seeder;

class ReReferenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $reReferences = [
            [
                'code' => 'RE-001',
                'name' => 'Retail Chain',
                'color' => '#8B5CF6'
            ],
            [
                'code' => 'RE-002',
                'name' => 'Independent Store',
                'color' => '#10B981'
            ],
            [
                'code' => 'RE-003',
                'name' => 'Supermarket',
                'color' => '#F59E0B'
            ],
            [
                'code' => 'RE-004',
                'name' => 'Pharmacy',
                'color' => '#EF4444'
            ],
            [
                'code' => 'RE-005',
                'name' => 'Convenience Store',
                'color' => '#06B6D4'
            ],
            [
                'code' => 'RE-006',
                'name' => 'Department Store',
                'color' => '#84CC16'
            ],
            [
                'code' => 'RE-007',
                'name' => 'Wholesale',
                'color' => '#F97316'
            ],
            [
                'code' => 'RE-008',
                'name' => 'Online Retailer',
                'color' => '#EC4899'
            ],
            [
                'code' => 'RE-009',
                'name' => 'Specialty Store',
                'color' => '#6B7280'
            ],
            [
                'code' => 'RE-010',
                'name' => 'Franchise',
                'color' => '#14B8A6'
            ]
        ];

        foreach ($reReferences as $reReference) {
            ReReference::create($reReference);
        }
    }
}