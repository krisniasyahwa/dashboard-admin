<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageItem extends Model
{
    use HasFactory;

    public function product(){
        return $this->belongsTo(Product::class, 'products_id', 'id');
    }

    public function productPackage(){
        return $this->belongsTo(ProductPackage::class, 'product_packages_id', 'id');
    }
}
