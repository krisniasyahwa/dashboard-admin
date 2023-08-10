<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductGallery extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'products_id', 'url', 'is_featured'
    ];

    //getUrlAttribute is a function to get the url of the image from the database, and convert it to a full url with the help of config('app.url) and Storage::url($url)
    public function getUrlAttribute($url)
    {
        return config('app.url') . Storage::url($url);
    }
}
