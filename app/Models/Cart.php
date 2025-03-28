<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        "user_id",
        "product_id",
        "quantity",
        "total_price",
    ];

    protected $hidden = [
        "created_at",
        "updated_at",
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function products(){
        return $this->belongsToMany(Product::class)->withPivot('quantity', 'price');
    }

    public function getTotalAttribute(){
        return $this->products->sum(function($product){
            return $product->pivot->price * $product->pivot->quantity;
        });
    }
}
