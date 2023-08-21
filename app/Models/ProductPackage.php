<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'status', 'price', 'best_seller', 'merchant_ud'
    ];

    public function merchant(){
        return $this->hasMany(Merchant::class, 'merchants_id', 'id');
    }
}
