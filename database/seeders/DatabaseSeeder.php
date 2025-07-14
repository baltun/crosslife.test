<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Run all seeders in proper order
        $this->call([
            UserSeeder::class,
            ProductSeeder::class,
            OrderSeeder::class,
        ]);
    }
}
        ]);
    }
}
