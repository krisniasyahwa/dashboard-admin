<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'role_name', 'home_url'
    ];

    public function user(){
        return $this->hasMany(User::class, 'role', 'id');
    }
}
