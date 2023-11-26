<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'merchants_id',
        'users_id',
        'address',
        'total_price',
        'transaction_type',
        'shipping_price',
        'takeaway_charge',
        'status',
        'payment',
        'payment_type',
        'status_payment',
        'payment_image'
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

    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'merchants_id', 'id');
    }

    public function getPaymentImageAttribute($path){
        if($path ==  null) return null;
        return config('app.url').Storage::url($path);
    }
}
