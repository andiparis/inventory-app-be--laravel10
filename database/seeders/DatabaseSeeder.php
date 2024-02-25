<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Stock;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
  /**
   * Seed the application's database.
   */
  public function run(): void
  {
    Item::create([
      'item_code'     => 'DRN0001',
      'name'          => 'DJI Ultra',
      'price'         => 50000000,
      'min_stock'     => 5,
      'description'   => 'Drone tercanggih di dunia saat ini',
    ]);
    Item::create([
      'item_code'     => 'DRN0002',
      'name'          => 'DJI Max',
      'price'         => 35000000,
      'min_stock'     => 10,
    ]);
  }
}
