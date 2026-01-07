<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\customCategory;

class customCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $category = array('Appartment Cleaning','Bedroom', 'Kitchen', 'Bathroom', 'Yard');
        $options = array('Less than 100 sq feet', '100 to 200 sq feet', '200 to 300 sq feet', '300 to 400 sq feet', '400 to 500 sq feet', 'More than 500 sq feet');
        for($i = 0; $i < count($category); $i++){
            for ($j = 0; $j < count($options); $j++) {
                customCategory::create([
                    'name' => $category[$i],
                    'option' => $options[$j],
                ]);
            }
        }
    }
}
