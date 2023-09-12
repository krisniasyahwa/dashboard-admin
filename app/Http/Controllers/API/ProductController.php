<?php

namespace App\Http\Controllers\API;

use App\Models\Product;
use App\Models\Merchant;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use Exception;
use Facade\FlareClient\Http\Response;

class ProductController extends Controller
{
    public function all(Request $request)
    {
        $id = $request->input('id');
        $limit = $request->input('limit', 6);
        $name = $request->input('name');
        $description = $request->input('description');
        $tags = $request->input('tags');
        $categories = $request->input('categories');
        $merchants = $request->input('merchants');
        $price_from = $request->input('price_from');
        $price_to = $request->input('price_to');
        $promo = $request->input('promo');
        $favorite = $request->input('favorite');

        if ($id) {
            //Join product table, category table and galleries table with id request
            $product = Product::with(['category', 'galleries'])->find($id);
            //Error handling, if product is not found
            //if product is found, return success response
            if ($product)
                return ResponseFormatter::success( //return result product with ResponseFormatter use message "Data produk berhasil diambil"
                    $product,
                    'Data produk berhasil diambil'
                );
            //if product is not found, return error response
            else
                return ResponseFormatter::error( //return error response use ResponseFormatter with message "Data produk tidak ada", and status code 404
                    null,
                    'Data produk tidak ada',
                    404
                );
        }

        //Join product table, category table, galleries table
        $product = Product::with(['category', 'galleries', 'featured_image', 'promo']);

        //if name request is not null, use where like to search product name
        if ($name)
            $product->where('name', 'like', '%' . $name . '%');
        //if description request is not null, use where like to search product description
        if ($description)
            $product->where('description', 'like', '%' . $description . '%');
        //if tags request is not null, use where like to search product tags
        if ($tags)
            $product->where('tags', 'like', '%' . $tags . '%');
        //if price_from request is not null, use where like to search product price_from
        if ($price_from)
            $product->where('price', '>=', $price_from);
        //if price_to request is not null, use where like to search product price_to
        if ($price_to)
            $product->where('price', '<=', $price_to);
        //if categories request is not null, use where like to search product categories
        if ($categories)
            $product->where('categories_id', $categories);
        //if merchants request is not null, use where like to search product merchants
        if ($merchants)
            $product->where('merchants_id', $merchants);
        // if ($promo)
        //     $product->where('promo_id', $promo);

        //if count product is o, return error response, with message "Data list produk kosong", and status code 404
        if ($product->count() == 0) {
            return ResponseFormatter::error(null, 'Data list produk kosong', 404);
        }
        //else if count product is not 0, return success response
        else {
            return ResponseFormatter::success(
                //use paginate to limit product list
                $product->paginate($limit),
                'Data list produk berhasil diambil'
            );
        }
    }



    //Functioin to get product best seller
    public function bestSeller(Request $request)
    {
        $limit = $request->input('limit', 12);
        try{
            $bestSeller = Product::with(['category', 'featured_image', 'galleries', 'promo', 'merchant'])->where('best_seller', 1)->take($limit)->get();
            if($bestSeller->count()!=0){
                return ResponseFormatter::success($bestSeller->paginate($limit), 'Data list produk best seller berhasil diambil');
            }else{
                return ResponseFormatter::error(null, 'Data list produk best seller kosong', 404);
            }
        } catch(Exception $error){
            return ResponseFormatter::error([
                'message' => 'Something went wrong',
                'error' => $error,
            ], 'Authentication Failed', 500);

        }
        

    }

    //Function to get random produt with limit ()
    public function randomProducts(Request $request)
    {
        $limit = $request->input('limit', 8);
        $categories = $request->input('categories');
        $merchants = $request->input('merchants');

        try{
            if ($merchants) {
                $randomProduct = Product::with(['category', 'featured_image', 'galleries', 'promo', 'merchant'])->inRandomOrder()->where('merchants_id', $merchants)->take($limit)->get();
    
                if ($randomProduct->count() != 0) {
                    return ResponseFormatter::success($randomProduct->paginate($limit), "Data list produk random berhasil diambil");
                } else {
                    return ResponseFormatter::error(null, 'Data list produk random kosong', 404);
                }
            }
            if ($categories) {
                $randomProduct = Product::with(['category', 'featured_image', 'galleries', 'promo', 'merchant'])->inRandomOrder()->where('categories_id', $categories)->take($limit)->get();
                if ($randomProduct->count() != 0) {
                    return ResponseFormatter::success($randomProduct->paginate($limit), "Data list produk random berhasil diambil");
                } else {
                    return ResponseFormatter::error(null, 'Data list produk random kosong', 404);
                }
            } else {
                $randomProduct = Product::with(['category', 'featured_image', 'galleries', 'promo', 'merchant'])->inRandomOrder()->take($limit)->get();
                return ResponseFormatter::success(
                    $randomProduct -> paginate($limit),
                    'Data list produk random berhasil diambil'
                );
            }

        }catch(Exception $error){
            return ResponseFormatter::error([
                'message' => 'Something went wrong',
                'error' => $error,
            ], 'Authentication Failed', 500);

        }
    }
}
