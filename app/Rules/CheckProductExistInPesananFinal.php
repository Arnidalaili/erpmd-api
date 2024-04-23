<?php

namespace App\Rules;

use App\Models\PesananFinalDetail;
use App\Models\Product;
use Illuminate\Contracts\Validation\Rule;

class CheckProductExistInPesananFinal implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
       
        $product = Product::find(request()->id);
        $oldProduct = $product->nama;

        $pesananFinalDetail = PesananFinalDetail::where('productid', request()->id)->get();
       
        if ($pesananFinalDetail) {
            if ($oldProduct != request()->nama) {
               return false;
            }
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'tidak bisa edit nama product karena sudah ada di pesanan final';
    }
}
