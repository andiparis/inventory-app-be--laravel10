<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
  use HasFactory;

  protected $guarded = ['id'];

  public function stock()
  {
    return $this->hasMany(Stock::class);
  }

  public function deliveryDetails()
  {
    return $this->hasMany(DeliveryDetails::class);
  }

  public function getRouteKeyName()
  {
    return 'item_code';
  }
}
