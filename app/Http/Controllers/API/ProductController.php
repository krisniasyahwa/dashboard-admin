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
            $product = Product::with(['category', 'galleries'])->find($id);

            if ($product)
                return ResponseFormatter::success(
                    $product,
                    'Data produk berhasil diambil'
                );
            else
                return ResponseFormatter::error(
                    null,
                    'Data produk tidak ada',
                    404
                );
        }

        $product = Product::with(['category', 'galleries', 'featured_image']);

        if ($name)
            $product->where('name', 'like', '%' . $name . '%');

        if ($description)
            $product->where('description', 'like', '%' . $description . '%');

        if ($tags)
            $product->where('tags', 'like', '%' . $tags . '%');

        if ($price_from)
            $product->where('price', '>=', $price_from);

        if ($price_to)
            $product->where('price', '<=', $price_to);

        if ($categories)
            $product->where('categories_id', $categories);

        if ($merchants)
            $product->where('merchants_id', $merchants);


        if ($product->count() == 0) {
            return ResponseFormatter::error(null, 'Data list produk kosong', 404);
        } else {
            return ResponseFormatter::success(
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
