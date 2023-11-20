<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;

class TransactionRequest extends FormRequest
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
        return [
            'status' => 'in:PENDING,PROCESS,READY,SUCCESS',
            'status_payment' => 'in:UNPAID,REVIEW,PAID,REJECTED,EXPIRED',
            'payment_image' => 'nullable|image',
            'transaction_type' => 'required|in:DINE_IN,TAKEAWAY',
            'payment_type' => 'required|in:BAYAR_SEKARANG,BAYAR_DITEMPAT',
            'payment' => 'required|in:QRIS,CASH'
        ];
    }
}
