<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class ProductCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'merchants_id',
        'image_path'
    ];
    //Create relationship with Products table->one to many->categories_id as foreign key
    public function products()
    {
        return $this->hasMany(Product::class, 'categories_id', 'id');
    }
    //create relationship with Merchants table->One to One -> merchants_id as foreign key
    public function merchants()
    {
        return $this->belongsTo(Merchant::class, 'merchants_id', 'id');
    }

    public function getImagePathAttribute($path)
    {
        if ($path == null) return null;

        return config('app.url') . Storage::url($path);
    }
}
