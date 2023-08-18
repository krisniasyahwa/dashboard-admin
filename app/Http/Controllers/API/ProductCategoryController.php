<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Models\ProductCategory;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;

class ProductCategoryController extends Controller
{
    //Create public function all with parameter request
    public function all(Request $request)
    {
        $id = $request->input('id'); //Variable id will store request id
        $limit = $request->input('limit', 6); //variable limit will store request limit, if limit is null, limit will be 6
        $name = $request->input('name'); //variable name will store request name 
        $show_product = $request->input('show_product'); //variable show_product will store request show_product
        $merchants = $request->input('merchants'); //variable merchants will store request merchants
        $image = $request->input('image');
        //if id request is not null, use find method to search use id request
        if ($id) {
            $category = ProductCategory::with(['products'])->find($id); //variable category will store result join product table and category table with id request
            //if category is found, return result category with ResponseFormatter use message "Data produk berhasil diambil"
            if ($category)
                return ResponseFormatter::success(
                    $category,
                    'Data produk berhasil diambil'
                );
            //if category is not found, return error response use ResponseFormatter with message "Data produk tidak ada", and status code 404
            else
                return ResponseFormatter::error(
                    null,
                    'Data kategori produk tidak ada',
                    404
                );
        }
        //variable category will store result query
        $category = ProductCategory::query();
        //if name request is not null, return category with name request use where like
        if ($name)
            $category->where('name', 'like', '%' . $name . '%');
        //if show_product request is not null, return category with show_product request use with method
        if ($show_product)
            $category->with('products');
        //if merchants request is not null, return category with merchants request use where
        if ($merchants)
            $category->where('merchants_id', $merchants);
        //if image request is not null, return category with image request use where
        if($image)
            $category->where('image',$image);
        //return category with paginate method and limit request
        if ($category->count() == 0) {
            return ResponseFormatter::error(null, 'Data list kategori produk kosong', 404); //return error response use ResponseFormatter with message "Data list kategori produk kosong", and status code 404
        } else { //if category is not null, return success response use ResponseFormatter with message "Data list kategori produk berhasil diambil"
            return ResponseFormatter::success(
                $category->paginate($limit), //return result category with paginate method and limit request
                'Data list kategori produk berhasil diambil'
            );
        }
    }
}
