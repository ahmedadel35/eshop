<?php

use App\Category;
use App\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $cats = Category::whereNotNull('category_id')->get();

        $cats->each(function (Category $c) {
            $c->products()->createMany(
                factory(Product::class, mt_rand(5, 20))->raw()
            );
        });
    }
}
