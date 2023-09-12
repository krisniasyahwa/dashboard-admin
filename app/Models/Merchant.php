<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Merchant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'address', 'phone', 'profile_photo_path','qris_path','concurrent_transaction'
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'merchants_id', 'id');
    }

    public function product_categories()
    {
        return $this->hasMany(ProductCategory::class, 'merchants_id', 'id');
    }

    public function getProfilePhotoPathAttribute($path)
    {
        if ($path == null) return null;

        return config('app.url') . Storage::url($path);
    }

    public function getQrisPathAttribute($path)
    {
        if ($path == null) return null;

        return config('app.url') . Storage::url($path);
    }

    public function MerchantVoucher(){
        return $this->hasMany(MerchantsVoucher::class, 'merchants_id', 'id');
    }

}
