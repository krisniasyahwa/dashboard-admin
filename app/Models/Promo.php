<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promo extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'promo_price', 'description', 'start_date', 'end_date', 'products_id'
    ];

    public function product(){
        return $this->hasMany(Product::class, 'products_id', 'id');
    }
}
