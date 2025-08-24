<?php

namespace Database\Seeders;

use App\Models\SalesPerson;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SalesPersonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $salesPeople = [
            [
                'name' => 'John Smith',
                'code' => 'JS001',
                'email' => 'john.smith@company.com',
                'phone' => '+1-555-0101',
                'type' => 'Sales Representative',
                'is_active' => true,
            ],
            [
                'name' => 'Sarah Johnson',
                'code' => 'SJ002',
                'email' => 'sarah.johnson@company.com',
                'phone' => '+1-555-0102',
                'type' => 'Sales Representative',
                'is_active' => true,
            ],
            [
                'name' => 'Michael Brown',
                'code' => 'MB003',
                'email' => 'michael.brown@distributor.com',
                'phone' => '+1-555-0103',
                'type' => 'Distributor',
                'is_active' => true,
            ],
            [
                'name' => 'Emily Davis',
                'code' => 'ED004',
                'email' => null,
                'phone' => '+1-555-0104',
                'type' => 'Sales Representative',
                'is_active' => false,
            ],
            [
                'name' => 'Robert Wilson',
                'code' => 'RW005',
                'email' => 'robert.wilson@company.com',
                'phone' => null,
                'type' => null,
                'is_active' => true,
            ]
        ];

        foreach ($salesPeople as $person) {
            SalesPerson::create($person);
        }
    }
}
