<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MerchantsVoucher extends Model
{

    protected $fillable = [
        'merchants_id', 'amount', 'groups_id'
    ];
    use HasFactory;
    public function Merchant(){
        return $this->hasMany(Merchant::class, 'merchants_id', 'id');
    }
}
