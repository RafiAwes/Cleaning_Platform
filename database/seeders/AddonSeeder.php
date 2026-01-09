<?php

namespace Database\Seeders;

use App\Models\Addon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AddonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $addons = [
            ['title' => 'Deep Cleaning'],
            ['title' => 'Carpet Cleaning'],
            ['title' => 'Window Cleaning'],
            ['title' => 'Upholstery Cleaning'],
            ['title' => 'Oven Cleaning'],
            ['title' => 'Refrigerator Cleaning'],
            ['title' => 'Laundry Service'],
            ['title' => 'Ironing Service'],
            ['title' => 'Dishwashing'],
            ['title' => 'Organizing & Decluttering'],
            ['title' => 'Pet Hair Removal'],
            ['title' => 'Eco-Friendly Products'],
            ['title' => 'Blinds Cleaning'],
            ['title' => 'Ceiling Fan Cleaning'],
            ['title' => 'Baseboards Cleaning'],
        ];

        foreach ($addons as $addon) {
            Addon::create($addon);
        }
    }
}
