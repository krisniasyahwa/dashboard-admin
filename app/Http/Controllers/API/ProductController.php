<?php

namespace App\Http\Controllers\API;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;

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
        $product = Product::with(['category', 'galleries', 'featured_image']);

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

    public function favorites(Request $request)
    {
        $merchants = $request->input('merchants');

        try {
            if ($merchants) {
                $product = Product::with(['category', 'galleries', 'featured_image'])->where('favorite', 1)->where('merchants_id', $merchants)->get();
            } else {
                $product = Product::with(['category', 'galleries', 'featured_image'])->where('favorite', 1)->get();
            }

            if ($product->count() == 0) {
                return ResponseFormatter::error(null, 'Data list produk favorite kosong', 404);
            } else {
                return ResponseFormatter::success(
                    $product,
                    'Data list produk favorite berhasil diambil'
                );
            }
        } catch (\Throwable $th) {
            return ResponseFormatter::error(
                null,
                'Data list produk favorite gagal diambil',
                500
            );
        }
    }
}
