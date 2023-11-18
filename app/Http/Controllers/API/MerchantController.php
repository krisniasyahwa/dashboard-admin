<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

use function Psy\debug;

class MerchantController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Show all merchants
        $merchants = Merchant::all();

        // Error handling if no merchants found
        if ($merchants->count() == 0) {
            return ResponseFormatter::error(null, 'No merchants found', 404);
        } else {
            return ResponseFormatter::success($merchants, 'Merchants found');
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validate request
        $request->validate([
            'name' => 'required|string',
            'address' => 'required|string',
            'phone' => 'required|string',
            'profile_photo_path' => 'nullable|string'
        ]);

        try {
            // Create merchant
            $merchant = Merchant::create([
                'name' => $request->name,
                'slug' => strtolower(str_replace(' ', '-', $request->name)),
                'address' => $request->address,
                'phone' => $request->phone,
                'profile_photo_path' => $request->profile_photo_path
            ]);

            // Error handling if merchant is not created
            if (!$merchant) {
                return ResponseFormatter::error(null, 'Merchant not created', 500);
            } else {
                return ResponseFormatter::success($merchant, 'Merchant created');
            }
        } catch (\Throwable $th) {
            return ResponseFormatter::error($th, 'Merchant not created', 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param slug
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // Show specific merchant by slug params
        $merchant = Merchant::find($id);

        // Error handling if merchant is not found
        if (!$merchant) {
            return ResponseFormatter::error(null, 'Merchant not found', 404);
        } else {
            return ResponseFormatter::success($merchant, 'Merchant found');
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Merchant  $merchant
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Merchant $merchant)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Merchant  $merchant
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // Delete merchant
        $merchant = Merchant::find($id);

        // Error handling if merchant is not found
        if (!$merchant) {
            return ResponseFormatter::error(null, 'Merchant not found', 404);
        } else {
            $merchant->delete();
            return ResponseFormatter::success(null, 'Merchant deleted');
        }
    }

    public function categories($merchant, Request $request)
    {
        $limit = $request->input('limit', 6);

        try {
            // Show all categories by merchant
            $categories = Merchant::find($merchant)->product_categories();

            // Error handling if no categories found
            if ($categories->count() == 0) {
                return ResponseFormatter::error(null, 'No categories found', 404);
            } else {
                return ResponseFormatter::success($categories->paginate($limit), 'Categories found');
            }
        } catch (\Throwable $th) {
            return ResponseFormatter::error($th, 'No categories found', 500);
        }
    }

    public function products($merchant, Request $request)
    {
        $limit = $request->input('limit', 6);

        try {
            // Show all products by merchant
            $products = Merchant::find($merchant)->first()->products()->with(['galleries','category']);

            // Error handling if no products found
            if ($products->count() == 0) {
                return ResponseFormatter::error(null, 'No products found', 404);
            } else {
                return ResponseFormatter::success($products->paginate($limit), 'Products found');
            }
        } catch (\Throwable $th) {
            return ResponseFormatter::error($th, 'No products found', 500);
        }
    }
}
