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
    /**
     * @OA\Get(
     *     path="/products",
     *     summary="Get All Products",
     *     tags={"Products"},
     *     operationId="products",
     *     @OA\Parameter(name="name",in="query",description="Show Data By Name",@OA\Schema(type="String")),
     *     @OA\Parameter(name="description",in="query",description="Show Data By Keyword description",@OA\Schema(type="String")),
     *     @OA\Parameter(name="categories",in="query",description="Show Data By Categories Id",@OA\Schema(type="String")),
     *     @OA\Parameter(name="merchants",in="query",description="Show Data By Merchants Id",@OA\Schema(type="String")),
     *     @OA\Parameter(name="limit",in="query",description="Filter Limitation Products",@OA\Schema(type="String")),
     *     @OA\Response(response="200", description="Success"),
     *     
     * )
     */


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
        $product = Product::with(['category', 'galleries', 'featured_image', 'promo'])->where('stock', '>=',1);

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
    /**
     * @OA\Get(
     * path="/products/bestseller",
     * tags={"Products"},
     * operationId="bestseller",
     * summary="Get Best Seller Products",
     * @OA\Parameter(name="limit",in="query",description="Adjust Data Limitation",@OA\Schema(type="String")),
     *  @OA\Response(response=200,description="Success Get Data Best Seller"),
     * )
     */

    /**
     * @OA\Get(
     * path="/categories",
     * tags={"Products By Category"},
     * operationId="categoriesproduct",
     * summary="Get Product By Categories",
     * @OA\Response(response=200,description="Success Get Data"),
     * )
     *
     */


    //Functioin to get product best seller
    public function bestSeller(Request $request)
    {
        $limit = $request->input('limit', 15);
        try{
            $bestSeller = Product::with(['category', 'featured_image', 'galleries', 'promo', 'merchant'])->where('best_seller', 1)->take($limit)->get();
            if($bestSeller->count()!=0){
                return ResponseFormatter::success($bestSeller, 'Data list produk best seller berhasil diambil');
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

    /**
     * @OA\Get(
     *  path="/products/random",
     *  summary="Get Random Products",
     *  tags={"Products"},
     *  operationId="randoms",
     *  @OA\Parameter(
     *      name="limit",
     *      description="set limitation random product",
     *      required=false,
     *      in="query",
     *      @OA\Schema(
     *          type="string"
     *      )
     *  ),
     *  @OA\Parameter(
     *      name="categories",
     *      description="set random product by categories",
     *      required=false,
     *      in="query",
     *      @OA\Schema(
     *          type="string",
     *      ),
     *  ),
     *  @OA\Parameter(
     *      name="merchants",
     *      description="set random products by merchants",
     *      required=false,
     *      in="query",
     *      @OA\Schema(
     *          type="string",
     *      ),
     *  ), 
     *  @OA\Response(
     *      response=200,
     *      description="Success Get Data Random Products",
     *      ),
     * )
     */


    //Function to get random produt with limit ()
    public function randomProducts(Request $request)
    {
        $limit = $request->input('limit', 10);
        $categories = $request->input('categories');
        $merchants = $request->input('merchants');

        try{
            if ($merchants) {
                $randomProduct = Product::with(['category', 'featured_image', 'galleries', 'promo', 'merchant'])->inRandomOrder()->where('stock','>=',1)->where('merchants_id',$merchants)->take($limit)->get();
                if ($randomProduct->count() != 0) {
                    return ResponseFormatter::success($randomProduct, "Data list produk random berhasil diambil");
                } else {
                    return ResponseFormatter::error(null, 'Data list produk random kosong', 404);
                }
            }
            if ($categories) {
                $randomProduct = Product::with(['category', 'featured_image', 'galleries', 'promo', 'merchant'])->inRandomOrder()->where('stock','>=',1)->where('categories_id',$categories)->take($limit)->get();
                if ($randomProduct->count() != 0) {
                    return ResponseFormatter::success($randomProduct, "Data list produk random berhasil diambil");
                } else {
                    return ResponseFormatter::error(null, 'Data list produk random kosong', 404);
                }
            } else {
                $randomProduct = Product::with(['category', 'featured_image', 'galleries', 'promo', 'merchant'])->inRandomOrder()->where('stock', '>=', 1)->take($limit)->get();
                return ResponseFormatter::success(
                    $randomProduct,
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

    /**
     * 
     *    
     */

    public function restore(Request $request){
        $id = $request->input('id');
        try{
            if(!$id){
                $product = Product::onlyTrashed()->get();
                $count = $product->count();
                if(!$product){
                    return ResponseFormatter::error($product, 'Product with id {{$id}}', 400);
            }
            }else{
                $restoredProduct = Product::onlyTrashed()->find($id);
                $restoredProduct->restore();
                $recycleBinProduct = Product::onlyTrashed()->get();
                if(!$restoredProduct){
                    return ResponseFormatter::error($restoredProduct, 'Product with id {{$id}}', 400);
                }
            }
            
            $result = [
                'restoredProduct' => $restoredProduct,
                'recycleBinProduct' => $recycleBinProduct
            ];
            return ResponseFormatter::success($result, 'success');


        }catch(Exception $error){
            [
                'message' => 'Something Happen',
                'error' => $error->getMessage(),
                'code' => 500

            ];
        }
        
    }
}
