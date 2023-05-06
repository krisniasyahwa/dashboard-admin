<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Merchant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'address', 'phone', 'profile_photo_path'
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'merchants_id', 'id');
    }

    public function product_categories()
    {
        return $this->hasMany(ProductCategory::class, 'merchants_id', 'id');
    }
}
