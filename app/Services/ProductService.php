<?php
namespace App\Services;
use App\Models\Product;

class ProductService{
    public function stockDecrement(array $items){
        foreach($items as $item){
            $product = Product::find($item['id'])->decrement('stock',$item['quantity']);
        }
        return $product;
    }
    
    public function stockIncrement($items){
        foreach($items as $item){
            $product = Product::find($item['products_id'])->increment('stock',$item['quantity']);
        }
        return $product;
    }

    public function stockValidation(array $items)
    {
        $stockAvailable = true;
        foreach($items as $item){
            $product = Product::find($item['id']);
            if(!$product || $product->stock < $item['quantity'] || $product->stock == 0){
                $stockAvailable = false;
                break;
            }
        }
        return $stockAvailable;
    }
}
?>

