<?php

namespace Database\Seeders;

use App\Enums\CustomerStatus;
use App\Enums\Priority;
use App\Models\Customer;
use Carbon\CarbonImmutable;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $date = CarbonImmutable::today();
        $customers = [
            [
                'Peter', 
                'General consultation', 
                '09:45', Priority::NORMAL,
                
            ],
            [
                'Mary', 
                'Urgent consultation', 
                '11:01', Priority::EMERGENCY
            ],
            [
                'John', 
                'Urgent consultation', 
                '11:04', Priority::EMERGENCY
            ],
            [
                'Susan', 
                'Prescription collection', 
                '10:25', Priority::PRIORITY
            ],
            [
                'Daniel', 
                'Lab results', 
                '10:50', Priority::NORMAL
            ],
        ];

        foreach ($customers as [$name, $service, $time, $priority]) {
            Customer::create([
                'name' => $name,
                'service' => $service,
                'arrival_at' => $date->setTimeFromTimeString($time),
                'original_priority' => $priority,
                'status' => CustomerStatus::Waiting,
            ]);
        }
    }
}
