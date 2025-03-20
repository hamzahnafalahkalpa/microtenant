<?php

namespace Database\Seeders\Installation;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Hanafalah\LaravelFeature\LaravelFeature;

class FeatureSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        LaravelFeature::useMasterFeature()->adds([
            ['name'   => 'User Management'],
            ['name'   => 'Role Management'],
            ['name'   => 'Feature Management']
        ]);
    }
}
