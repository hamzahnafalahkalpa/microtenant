<?php

namespace Database\Seeders\Installation;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InstallationSeeder extends Seeder{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // $this->command->info('Seeding the default data...');
        $this->call([
            FeatureSeeder::class
        ]);
        // $this->command->info('Done!');
    }
}