<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'description', 'price', 'categories_id', 'tags', 'favorite', 'merchants_id'
    ];

    public function galleries()
    {
        return $this->hasMany(ProductGallery::class, 'products_id', 'id');
    }

    public function featured_image()
    {
        try {
            $featured = $this->hasOne(ProductGallery::class, 'products_id', 'id')->where('is_featured', 1);

            // Error handling, if there is no featured image
            if (!$featured)
                return $featured;
            else
                return $this->hasOne(ProductGallery::class, 'products_id', 'id');
        } catch (\Throwable $th) {
            return null;
        }
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'categories_id', 'id');
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'merchants_id', 'id');
    }
}
