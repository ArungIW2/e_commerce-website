<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Store Admin', 'password' => 'password', 'role' => 'admin']
        );

        foreach (['Electronics', 'Fashion', 'Home & Living', 'Accessories'] as $name) {
            $category = Category::create(['name' => $name, 'slug' => Str::slug($name)]);
            for ($i = 1; $i <= 3; $i++) {
                $productName = $name.' Product '.$i;
                Product::create([
                    'category_id' => $category->id,
                    'name' => $productName,
                    'slug' => Str::slug($productName),
                    'sku' => strtoupper(Str::random(10)),
                    'description' => 'Demo product for the Laravel Commerce catalog.',
                    'price' => random_int(50000, 2000000),
                    'stock' => random_int(5, 100),
                ]);
            }
        }
    }
}
