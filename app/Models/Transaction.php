<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'users_id', 'address', 'payment', 'total_price', 'shipping_price', 'status', 'point_usage'
    ];
    //Create relationship with Users table->One to Many->users_id as foreign key
    public function user()
    {
        return $this->belongsTo(User::class, 'users_id', 'id');
    }
    //Create relationship with transaction items table->One to Many->transactions_id as foreign key
    public function items()
    {
        return $this->hasMany(TransactionItem::class, 'transactions_id', 'id');
    }
}
