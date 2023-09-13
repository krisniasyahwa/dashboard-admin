<?php

namespace App\Http\Requests;

use App\Models\Merchant;
use App\Models\ProductCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        // Define a custom validation rule to check if the combination of merchant_id and category_id exists in the product_categories table.
        $customRule = function ($attribute, $value, $fail) {
            $merchantId = $this->input('merchants_id');
            $categoryId = $value;

            $exists = ProductCategory::select()
                ->where('merchants_id', $merchantId)
                ->where('id', $categoryId)
                ->exists();

                if (!$exists) {
                    $merchantName = Merchant::select('name')->where('id', $merchantId)->first()->name;
                    $categoryName = ProductCategory::select('name')->where('id', $categoryId)->first()->name;

                    $fail("Categories is invalid. The selected Categories ($categoryName) is not associated with the selected merchant ($merchantName).");
                }
        };

        return [
            'name' => 'required|max:255|min:3',
            'description' => 'required|max:255|min:3',
            'stock' => 'required|integer|max:999|min:0',
            'price' => 'required|integer|min:0',
            'promo_price' => 'integer|min:0',
            'takeway_charge' => 'integer|min:0',
            'merchants_id' => 'required|exists:merchants,id',
            'categories_id' => [
                'required',
                'exists:product_categories,id',
                $customRule,
            ],
        ];
    }

     /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            // Add custom error messages if needed.
        ];
    }
}

