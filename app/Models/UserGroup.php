<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserGroup extends Model
{
    use HasFactory;

    public function user(){
        return $this->hasMany(User::class, 'users_id', 'id');
    }

    public function group(){
        return $this->belongsTo(Group::class, 'groups_id', 'id');
    }
}

