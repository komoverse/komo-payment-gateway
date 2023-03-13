<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
use Illuminate\Support\Facades\Validator;
use App\Models\ShardTransactionModel;

class PaypalController extends Controller
{
    public function callback(Request $request) {
        if (ShardTransactionModel::submitCallback($request->post(), 'paypal')) {
            return response()->json('Callback received '.date('Y-m-d H:i:s'), 200);
        }
    }

    public function generatePaypalLink($request) {
        $rules = [
            'currency' => 'required|string',
            'pay_amount' => 'required|numeric',
        ];

        $validator = Validator::make($request, $rules);
        if ($validator->fails()) {
            return $validator->errors();
        } else {
            $request = (object) $request;
        }

        $provider = new PayPalClient;
        $provider->getAccessToken();

        $data = json_decode('{
            "intent": "CAPTURE",
            "purchase_units": [
              {
                "amount": {
                  "currency_code": "'.$request->currency.'",
                  "value": "'.round($request->pay_amount, 2, PHP_ROUND_HALF_UP).'"
                }
              }
            ]
        }', true);
        $paypal = $provider->createOrder($data);

        return $paypal;
    }
}
